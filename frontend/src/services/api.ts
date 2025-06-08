import axios, { type AxiosInstance } from "axios";
import type { User, Weather, ApiResponse } from "@/types/user";

class ApiClient {
  private client: AxiosInstance;

  constructor(baseURL: string = "/api") {
    this.client = axios.create({
      baseURL,
      timeout: 10000,
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
    });

    // Response interceptor for error handling
    this.client.interceptors.response.use(
      (response) => response.data,
      (error) => {
        console.error("API Error:", error.response?.data || error.message);
        throw error;
      }
    );
  }

  async getUsers(): Promise<ApiResponse<User[]>> {
    return this.client.get("/users");
  }

  async getUser(id: number): Promise<ApiResponse<User>> {
    return this.client.get(`/users/${id}`);
  }

  async getUserWeather(userId: number): Promise<ApiResponse<Weather>> {
    return this.client.get(`/users/${userId}/weather`);
  }

  async refreshWeather(userId: number): Promise<ApiResponse<Weather>> {
    return this.client.post(`/users/${userId}/weather/refresh`);
  }

  async getAllWeather(
    page: number = 1,
    perPage: number = 15
  ): Promise<ApiResponse<any[]>> {
    return this.client.get("/weather", {
      params: { page, per_page: perPage },
    });
  }
}

export const api = new ApiClient();
