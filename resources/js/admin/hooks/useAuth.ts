import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { User } from '@/types';

interface LoginCredentials {
    email: string;
    password: string;
}

export function useAuth() {
    const queryClient = useQueryClient();

    const { data: user, isLoading, error } = useQuery<User | null>({
        queryKey: ['auth', 'user'],
        queryFn: async () => {
            try {
                const response = await api.get('/user');
                return response.data;
            } catch {
                return null;
            }
        },
        staleTime: Infinity,
        retry: false,
    });

    const loginMutation = useMutation({
        mutationFn: async (credentials: LoginCredentials) => {
            await api.get('/sanctum/csrf-cookie', { baseURL: '' });
            const response = await api.post('/login', credentials);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['auth', 'user'] });
        },
    });

    const logoutMutation = useMutation({
        mutationFn: async () => {
            await api.post('/logout');
        },
        onSuccess: () => {
            queryClient.setQueryData(['auth', 'user'], null);
            window.location.href = '/admin/login';
        },
    });

    return {
        user,
        isLoading,
        error,
        login: loginMutation.mutate,
        loginAsync: loginMutation.mutateAsync,
        isLoggingIn: loginMutation.isPending,
        loginError: loginMutation.error,
        logout: logoutMutation.mutate,
        isLoggingOut: logoutMutation.isPending,
    };
}
