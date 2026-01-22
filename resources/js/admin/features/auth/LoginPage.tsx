import { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAuth } from '@/hooks/useAuth';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const loginSchema = z.object({
    email: z.string().email('유효한 이메일을 입력해주세요'),
    password: z.string().min(1, '비밀번호를 입력해주세요'),
});

type LoginFormData = z.infer<typeof loginSchema>;

export default function LoginPage() {
    const navigate = useNavigate();
    const { user, loginAsync, isLoggingIn, loginError } = useAuth();

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<LoginFormData>({
        resolver: zodResolver(loginSchema),
    });

    useEffect(() => {
        if (user) {
            navigate('/', { replace: true });
        }
    }, [user, navigate]);

    const onSubmit = async (data: LoginFormData) => {
        try {
            await loginAsync(data);
            window.location.href = '/admin';
        } catch {
            // Error is handled by the mutation
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-[hsl(var(--background))] p-4">
            <Card className="w-full max-w-md">
                <CardHeader className="space-y-1">
                    <CardTitle className="text-2xl font-bold text-center">
                        Lumia Lab Admin
                    </CardTitle>
                    <CardDescription className="text-center">
                        관리자 계정으로 로그인해주세요
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="email">이메일</Label>
                            <Input
                                id="email"
                                type="email"
                                placeholder="admin@example.com"
                                {...register('email')}
                            />
                            {errors.email && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.email.message}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="password">비밀번호</Label>
                            <Input
                                id="password"
                                type="password"
                                {...register('password')}
                            />
                            {errors.password && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.password.message}
                                </p>
                            )}
                        </div>
                        {loginError && (
                            <p className="text-sm text-[hsl(var(--destructive))]">
                                로그인에 실패했습니다. 이메일과 비밀번호를 확인해주세요.
                            </p>
                        )}
                        <Button type="submit" className="w-full" disabled={isLoggingIn}>
                            {isLoggingIn ? '로그인 중...' : '로그인'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
