import { ref, onUnmounted } from 'vue';
import { io } from 'socket.io-client';

export function useClinicSocket(clinicIdRef, socketTokenRef, onQueueUpdated) {
  const socket = ref(null);
  const isConnected = ref(false);

  const initSocket = () => {
    const socketUrl = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3000';
    if (socket.value) {
      socket.value.disconnect();
    }

    socket.value = io(socketUrl, {
      transports: ['websocket', 'polling'],
      reconnectionAttempts: 10,
      reconnectionDelay: 2000,
    });

    socket.value.on('connect', () => {
      isConnected.value = true;
      const cid = typeof clinicIdRef === 'function' ? clinicIdRef() : (clinicIdRef?.value ?? clinicIdRef);
      const token = typeof socketTokenRef === 'function' ? socketTokenRef() : (socketTokenRef?.value ?? socketTokenRef);
      if (cid) {
        socket.value.emit('join_clinic', {
          clinicId: cid,
          token: token || '',
        });
      }
    });

    socket.value.on('queue:updated', (data) => {
      if (typeof onQueueUpdated === 'function') {
        onQueueUpdated(data);
      }
    });

    socket.value.on('queue_updated', (data) => {
      if (typeof onQueueUpdated === 'function') {
        onQueueUpdated(data);
      }
    });

    socket.value.on('disconnect', () => {
      isConnected.value = false;
    });
  };

  const disconnect = () => {
    if (socket.value) {
      socket.value.disconnect();
      socket.value = null;
    }
  };

  onUnmounted(() => {
    disconnect();
  });

  return {
    socket,
    isConnected,
    initSocket,
    disconnect,
  };
}
