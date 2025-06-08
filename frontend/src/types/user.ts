export interface User {
  id: number;
  name: string;
  email: string;
  location: {
    latitude: number;
    longitude: number;
  };
  weather?: Weather;
}

export interface Weather {
  current: {
    temperature: number;
    feels_like: number;
    description: string;
    main: string;
    icon: string;
    humidity: number;
    wind_speed: number;
    pressure: number;
    clouds: number;
    visibility: number;
    rain?: number;
    snow?: number;
    uvi?: number;
  };
  cached_at: string;
  response_time_ms?: number;
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
}
