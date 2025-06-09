import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Make Pusher available globally for Laravel Echo
window.Pusher = Pusher;

// Configure Laravel Echo
export const echo = new Echo({
  broadcaster: 'pusher',
  key: import.meta.env.VITE_PUSHER_APP_KEY || 'app-key',
  cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
  wsHost: import.meta.env.VITE_PUSHER_HOST || 'localhost',
  wsPort: import.meta.env.VITE_PUSHER_PORT || 6001,
  wssPort: import.meta.env.VITE_PUSHER_PORT || 6001,
  forceTLS: false,
  encrypted: false,
  disableStats: true,
  enabledTransports: ['ws'],
});

// Export connection status helpers
export const isConnected = () => {
  return echo.connector.pusher.connection.state === 'connected';
};

export const onConnectionStateChange = (callback: (state: string) => void) => {
  echo.connector.pusher.connection.bind('state_change', (states: any) => {
    callback(states.current);
  });
};