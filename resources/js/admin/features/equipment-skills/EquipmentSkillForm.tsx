import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { EquipmentSkill } from '@/types';

const skillSchema = z.object({
    name: z.string().min(1, '이름을 입력해주세요'),
    grade: z.string().nullable(),
    sub_category: z.string().nullable(),
    description: z.string().nullable(),
});

export type SkillFormData = z.infer<typeof skillSchema>;

interface EquipmentSkillFormProps {
    skill?: EquipmentSkill;
    onSubmit: (data: SkillFormData) => void;
    onCancel: () => void;
    isSubmitting: boolean;
}

export function EquipmentSkillForm({
    skill,
    onSubmit,
    onCancel,
    isSubmitting,
}: EquipmentSkillFormProps) {
    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<SkillFormData>({
        resolver: zodResolver(skillSchema),
        defaultValues: skill || {
            name: '',
            grade: null,
            sub_category: null,
            description: null,
        },
    });

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <Card>
                <CardHeader>
                    <CardTitle>스킬 정보</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="space-y-2">
                        <Label htmlFor="name">이름 *</Label>
                        <Input id="name" {...register('name')} />
                        {errors.name && (
                            <p className="text-sm text-[hsl(var(--destructive))]">
                                {errors.name.message}
                            </p>
                        )}
                    </div>

                    <div className="grid gap-4 md:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="grade">등급</Label>
                            <Input id="grade" {...register('grade')} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sub_category">서브 카테고리</Label>
                            <Input id="sub_category" {...register('sub_category')} />
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">설명</Label>
                        <Textarea id="description" rows={5} {...register('description')} />
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
