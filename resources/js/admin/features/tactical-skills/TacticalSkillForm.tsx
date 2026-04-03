import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TacticalSkill } from '@/types';

const skillSchema = z.object({
    name: z.string().min(1, '이름을 입력해주세요'),
    tooltip: z.string().nullable(),
});

export type TacticalSkillFormData = z.infer<typeof skillSchema>;

interface TacticalSkillFormProps {
    skill?: TacticalSkill;
    onSubmit: (data: TacticalSkillFormData) => void;
    onCancel: () => void;
    isSubmitting: boolean;
}

export function TacticalSkillForm({ skill, onSubmit, onCancel, isSubmitting }: TacticalSkillFormProps) {
    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<TacticalSkillFormData>({
        resolver: zodResolver(skillSchema),
        defaultValues: skill
            ? { name: skill.name, tooltip: skill.tooltip }
            : { name: '', tooltip: null },
    });

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <Card>
                <CardHeader>
                    <CardTitle>전술스킬 정보</CardTitle>
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

                    <div className="space-y-2">
                        <Label htmlFor="tooltip">툴팁</Label>
                        <Textarea id="tooltip" rows={4} {...register('tooltip')} />
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
