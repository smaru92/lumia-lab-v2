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
    is_main: z.number().nullable(),
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
                  is_main: trait.is_main,
                  category: trait.category,
              }
            : { name: '', tooltip: null, is_main: null, category: null },
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
                            <Label>메인 여부</Label>
                            <Controller
                                name="is_main"
                                control={control}
                                render={({ field }) => (
                                    <Select
                                        value={field.value != null ? String(field.value) : ''}
                                        onValueChange={(v) =>
                                            field.onChange(v === '' ? null : Number(v))
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="선택..." />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="1">메인 특성</SelectItem>
                                            <SelectItem value="0">서브 특성</SelectItem>
                                        </SelectContent>
                                    </Select>
                                )}
                            />
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
