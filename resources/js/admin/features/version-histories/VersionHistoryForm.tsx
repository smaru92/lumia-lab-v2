import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { VersionHistory } from '@/types';

const versionSchema = z.object({
    version_season: z.coerce.number().nullable(),
    version_major: z.coerce.number().min(0, '필수 입력'),
    version_minor: z.coerce.number().min(0, '필수 입력'),
    start_date: z.string().min(1, '시작일을 입력해주세요'),
    end_date: z.string().min(1, '종료일을 입력해주세요'),
});

export type VersionFormData = z.infer<typeof versionSchema>;

interface VersionHistoryFormProps {
    version?: VersionHistory;
    onSubmit: (data: VersionFormData) => void;
    onCancel: () => void;
    isSubmitting: boolean;
}

export function VersionHistoryForm({
    version,
    onSubmit,
    onCancel,
    isSubmitting,
}: VersionHistoryFormProps) {
    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<VersionFormData>({
        resolver: zodResolver(versionSchema),
        defaultValues: version
            ? {
                  ...version,
                  start_date: version.start_date?.slice(0, 10),
                  end_date: version.end_date?.slice(0, 10),
              }
            : {
                  version_season: null,
                  version_major: 0,
                  version_minor: 0,
                  start_date: '',
                  end_date: '',
              },
    });

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <Card>
                <CardHeader>
                    <CardTitle>버전 정보</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid gap-4 md:grid-cols-3">
                        <div className="space-y-2">
                            <Label htmlFor="version_season">시즌</Label>
                            <Input
                                id="version_season"
                                type="number"
                                placeholder="선택 사항"
                                {...register('version_season')}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="version_major">메이저 버전 *</Label>
                            <Input
                                id="version_major"
                                type="number"
                                {...register('version_major')}
                            />
                            {errors.version_major && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.version_major.message}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="version_minor">마이너 버전 *</Label>
                            <Input
                                id="version_minor"
                                type="number"
                                {...register('version_minor')}
                            />
                            {errors.version_minor && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.version_minor.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="start_date">시작일 *</Label>
                            <Input id="start_date" type="date" {...register('start_date')} />
                            {errors.start_date && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.start_date.message}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="end_date">종료일 *</Label>
                            <Input id="end_date" type="date" {...register('end_date')} />
                            {errors.end_date && (
                                <p className="text-sm text-[hsl(var(--destructive))]">
                                    {errors.end_date.message}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={onCancel}>
                            취소
                        </Button>
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? '저장 중...' : '저장'}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </form>
    );
}
