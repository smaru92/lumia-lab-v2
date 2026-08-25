import { useForm, Controller } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { GameTrait } from '@/types';

const traitSchema = z.object({
    name: z.string().min(1, '이름을 입력해주세요'),
    tooltip: z.string().nullable(),
    // 메인 여부(is_main)는 그룹에서 서버가 파생시키므로 폼에서는 그룹만 다룬다.
    trait_group: z.string().nullable(),
    category: z.string().nullable(),
});

export type TraitFormData = z.infer<typeof traitSchema>;

interface TraitFormProps {
    trait?: GameTrait;
    onSubmit: (data: TraitFormData) => void;
    onCancel: () => void;
    isSubmitting: boolean;
}

export function TraitForm({ trait, onSubmit, onCancel, isSubmitting }: TraitFormProps) {
    const {
        register,
        handleSubmit,
        control,
        formState: { errors },
    } = useForm<TraitFormData>({
        resolver: zodResolver(traitSchema),
        defaultValues: trait
            ? {
                  name: trait.name,
                  tooltip: trait.tooltip,
                  trait_group: trait.trait_group,
                  category: trait.category,
              }
            : { name: '', tooltip: null, trait_group: null, category: null },
    });

    return (
        <form onSubmit={handleSubmit(onSubmit)}>
            <Card>
                <CardHeader>
                    <CardTitle>특성 정보</CardTitle>
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
                            <Label>특성 그룹</Label>
                            <Controller
                                name="trait_group"
                                control={control}
                                render={({ field }) => (
                                    <Select
                                        value={field.value ?? ''}
                                        onValueChange={(v) => field.onChange(v === '' ? null : v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="선택..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="main">메인</SelectItem>
                                            <SelectItem value="sub1">서브1</SelectItem>
                                            <SelectItem value="sub2">서브2</SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
                            <p className="text-xs text-[hsl(var(--muted-foreground))]">
                                그룹을 바꾸면 현재 진행중인 버전 기준으로 변경 이력이 기록됩니다.
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="category">카테고리</Label>
                            <Input id="category" {...register('category')} />
                        </div>
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
