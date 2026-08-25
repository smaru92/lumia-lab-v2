import { useEffect } from 'react';
import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from '@/hooks/useToast';

const settingsSchema = z.object({
    default_version_mode: z.enum(['auto', 'manual']),
    default_version: z.string().nullable(),
    default_version_delay_hours: z.coerce.number().min(0, '0 이상').max(720, '720 이하'),
    default_tier: z.string().min(1, '필수 선택'),
    main_page_tier: z.string().min(1, '필수 선택'),
});

type SettingsFormData = z.infer<typeof settingsSchema>;

interface VersionOption {
    value: string;
    label: string;
    start_date: string | null;
}

interface SettingsResponse {
    data: SettingsFormData & {
        resolved_default_version: string;
        auto_default_version: string;
    };
    options: {
        tiers: string[];
        versions: VersionOption[];
    };
}

const TIER_LABELS: Record<string, string> = {
    All: '전체',
    Platinum: '플래티넘',
    Diamond: '다이아',
    Diamond2: '다이아2',
    Meteorite: '메테오라이트',
    Mithrillow: '미스릴',
    Mithrilhigh: '미스릴(8000+)',
    Top: '최상위큐',
};

export default function SettingsPage() {
    const queryClient = useQueryClient();

    const { data, isLoading } = useQuery<SettingsResponse>({
        queryKey: ['settings'],
        queryFn: async () => (await api.get('/settings')).data,
    });

    const {
        register,
        handleSubmit,
        control,
        reset,
        watch,
        formState: { errors },
    } = useForm<SettingsFormData>({
        resolver: zodResolver(settingsSchema),
        defaultValues: {
            default_version_mode: 'auto',
            default_version: null,
            default_version_delay_hours: 24,
            default_tier: 'Diamond',
            main_page_tier: 'Meteorite',
        },
    });

    // 서버 값이 도착하면 폼에 반영
    useEffect(() => {
        if (data?.data) {
            reset({
                default_version_mode: data.data.default_version_mode,
                default_version: data.data.default_version ?? null,
                default_version_delay_hours: data.data.default_version_delay_hours,
                default_tier: data.data.default_tier,
                main_page_tier: data.data.main_page_tier,
            });
        }
    }, [data, reset]);

    const mutation = useMutation({
        mutationFn: async (form: SettingsFormData) => (await api.put('/settings', form)).data,
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['settings'] });
            toast({ title: '저장 완료', description: '설정이 저장되었습니다.' });
        },
        onError: (error: any) => {
            toast({
                title: '저장 실패',
                description: error?.response?.data?.message ?? '설정 저장에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const mode = watch('default_version_mode');
    const tiers = data?.options.tiers ?? [];
    const versions = data?.options.versions ?? [];

    if (isLoading) {
        return (
            <div className="flex h-64 items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <PageHeader
                title="사이트 설정"
                description="통계 페이지의 기본 버전과 기본 티어를 조절합니다."
            />

            <form onSubmit={handleSubmit((form) => mutation.mutate(form))} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>기본 버전</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="rounded-md bg-[hsl(var(--muted))] p-3 text-sm">
                            현재 적용중:{' '}
                            <strong>{data?.data.resolved_default_version}</strong>
                            <span className="text-[hsl(var(--muted-foreground))]">
                                {' '}
                                (자동 계산값: {data?.data.auto_default_version})
                            </span>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>버전 선택 방식</Label>
                                <Controller
                                    name="default_version_mode"
                                    control={control}
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="auto">자동 (최신 버전 추종)</SelectItem>
                                                <SelectItem value="manual">수동 지정</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="default_version_delay_hours">승격 대기 시간 (시간)</Label>
                                <Input
                                    id="default_version_delay_hours"
                                    type="number"
                                    min={0}
                                    max={720}
                                    disabled={mode !== 'auto'}
                                    {...register('default_version_delay_hours')}
                                />
                                {errors.default_version_delay_hours && (
                                    <p className="text-sm text-[hsl(var(--destructive))]">
                                        {errors.default_version_delay_hours.message}
                                    </p>
                                )}
                                <p className="text-xs text-[hsl(var(--muted-foreground))]">
                                    새 버전이 등장하고 이 시간이 지나야 기본 버전이 됩니다. 바로 바꾸면 통계 표본이
                                    거의 없어 화면이 비어 보입니다.
                                </p>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label>수동 지정 버전</Label>
                            <Controller
                                name="default_version"
                                control={control}
                                render={({ field }) => (
                                    <Select
                                        value={field.value ?? ''}
                                        onValueChange={(v) => field.onChange(v === '' ? null : v)}
                                        disabled={mode !== 'manual'}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="버전 선택..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {versions.map((v) => (
                                                <SelectItem key={v.value} value={v.value}>
                                                    {v.label}
                                                    {v.start_date ? ` (${v.start_date})` : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            <p className="text-xs text-[hsl(var(--muted-foreground))]">
                                수동 모드일 때만 사용됩니다.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>기본 티어</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label>캐릭터/상세 페이지</Label>
                                <Controller
                                    name="default_tier"
                                    control={control}
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tiers.map((t) => (
                                                    <SelectItem key={t} value={t}>
                                                        {TIER_LABELS[t] ?? t}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>메인 페이지</Label>
                                <Controller
                                    name="main_page_tier"
                                    control={control}
                                    render={({ field }) => (
                                        <Select value={field.value} onValueChange={field.onChange}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tiers.map((t) => (
                                                    <SelectItem key={t} value={t}>
                                                        {TIER_LABELS[t] ?? t}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    )}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={mutation.isPending}>
                        {mutation.isPending ? '저장 중...' : '저장'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
