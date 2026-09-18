# WebSocket contract roadmap

WebSocket streaming is not required for standalone operation. The current workstation loads local snapshots and refreshes through authenticated WordPress REST requests; its matrix row remains `NOT_STARTED`.

If a local WebSocket mode is later added, it must be optional. The default implementation will continue to use WordPress REST polling so no daemon or external gateway is needed.
