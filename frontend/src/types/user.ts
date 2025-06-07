export interface User {
  id: number
  name: string
  email: string
  location: {
    latitude: number
    longitude: number
  }
  weather?: Weather
}

export interface Weather {
  current: {
    temperature: number
    feels_like: number
    description: string
    icon: string
    humidity: number
    wind_speed: number
    pressure: number
    rain?: number
    snow?: number
  }
  cached_at: string
  location: {
    latitude: number
    longitude: number
  }
}

export interface ApiResponse<T> {
  data: T
  message?: string
  meta?: {
    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
  }
}