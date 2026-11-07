import { describe, it, expect, vi, beforeEach } from 'vitest';
import { useClinicSocket } from '../../src/composables/useClinicSocket';
import { io } from 'socket.io-client';

vi.mock('socket.io-client', () => {
  const mSocket = {
    on: vi.fn(),
    emit: vi.fn(),
    disconnect: vi.fn(),
  };
  return {
    io: vi.fn(() => mSocket),
  };
});

describe('useClinicSocket Composable', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('initializes socket and emits join_clinic with token on connect', () => {
    const onUpdated = vi.fn();
    const { initSocket, isConnected } = useClinicSocket(101, 'test_hmac_token', onUpdated);

    initSocket();

    expect(io).toHaveBeenCalled();
    const mockSocket = io();

    // Find connect callback
    const connectCall = mockSocket.on.mock.calls.find(c => c[0] === 'connect');
    expect(connectCall).toBeDefined();

    // Trigger connect event
    connectCall[1]();

    expect(mockSocket.emit).toHaveBeenCalledWith('join_clinic', {
      clinicId: 101,
      token: 'test_hmac_token',
    });
    expect(isConnected.value).toBe(true);
  });

  it('triggers onQueueUpdated callback when queue:updated event is received', () => {
    const onUpdated = vi.fn();
    const { initSocket } = useClinicSocket(101, 'test_hmac_token', onUpdated);

    initSocket();
    const mockSocket = io();

    const queueUpdateCall = mockSocket.on.mock.calls.find(c => c[0] === 'queue:updated');
    expect(queueUpdateCall).toBeDefined();

    const payload = { clinic_id: 101, shift_id: 2 };
    queueUpdateCall[1](payload);

    expect(onUpdated).toHaveBeenCalledWith(payload);
  });

  it('disconnects socket properly', () => {
    const { initSocket, disconnect } = useClinicSocket(101, 'token', vi.fn());
    initSocket();
    const mockSocket = io();

    disconnect();
    expect(mockSocket.disconnect).toHaveBeenCalled();
  });
});
