#![cfg_attr(not(debug_assertions), windows_subsystem = "windows")]

use tauri::{AppHandle, Manager, WebviewUrl, WebviewWindowBuilder};

#[tauri::command]
async fn open_tv_on_secondary_monitor(app: AppHandle, tv_code: String) -> Result<(), String> {
    // Check if waiting_tv window already exists
    if let Some(existing_window) = app.get_webview_window("waiting_tv") {
        existing_window.show().map_err(|e| e.to_string())?;
        existing_window.set_focus().map_err(|e| e.to_string())?;
        return Ok(());
    }

    // Get available monitors
    let monitors = app.available_monitors().map_err(|e| e.to_string())?;
    
    // Correction 8: Use WebviewUrl::App with hash route (no hardcoded localhost port)
    let tv_url = WebviewUrl::App(format!("index.html#/tv/{}", tv_code).into());

    let mut builder = WebviewWindowBuilder::new(&app, "waiting_tv", tv_url)
        .title("Waiting Room TV Kiosk")
        .fullscreen(true)
        .resizable(true);

    // If secondary monitor exists (index 1), place on it
    if monitors.len() > 1 {
        let secondary = &monitors[1];
        let pos = secondary.position();
        builder = builder.position(pos.x as f64, pos.y as f64);
    }

    builder.build().map_err(|e| e.to_string())?;

    Ok(())
}

fn main() {
    tauri::Builder::default()
        .invoke_handler(tauri::generate_handler![open_tv_on_secondary_monitor])
        .run(tauri::generate_context!())
        .expect("error while running tauri application");
}
