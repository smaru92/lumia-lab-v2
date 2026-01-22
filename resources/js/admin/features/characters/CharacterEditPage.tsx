import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import api from '@/lib/axios';
import { Character, CharacterTag } from '@/types';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { toast } from '@/hooks/useToast';
import { CharacterTagsManager } from './CharacterTagsManager';

const characterSchema = z.object({
    name: z.string().nullable(),
    max_hp: z.coerce.number().nullable(),
    max_hp_by_lv: z.coerce.number().nullable(),
    max_mp: z.coerce.number().nullable(),
    max_mp_by_lv: z.coerce.number().nullable(),
    init_extra_point: z.coerce.number().nullable(),
    max_extra_point: z.coerce.number().nullable(),
    attack_power: z.coerce.number().nullable(),
    attack_power_by_lv: z.coerce.number().nullable(),
    deffence: z.coerce.number().nullable(),
    deffence_by_lv: z.coerce.number().nullable(),
    hp_regen: z.coerce.number().nullable(),
    hp_regen_by_lv: z.coerce.number().nullable(),
    sp_regen: z.coerce.number().nullable(),
    sp_regen_by_lv: z.coerce.number().nullable(),
    attack_speed: z.coerce.number().nullable(),
    attack_speed_limit: z.coerce.number().nullable(),
    attack_speed_min: z.coerce.number().nullable(),
    move_speed: z.coerce.number().nullable(),
    sight_range: z.coerce.number().nullable(),
});

type CharacterFormData = z.infer<typeof characterSchema>;

const fields: { name: keyof CharacterFormData; label: string }[] = [
    { name: 'name', label: '이름' },
    { name: 'max_hp', label: '최대 HP' },
    { name: 'max_hp_by_lv', label: '레벨당 최대 HP' },
    { name: 'max_mp', label: '최대 MP' },
    { name: 'max_mp_by_lv', label: '레벨당 최대 MP' },
    { name: 'init_extra_point', label: '초기 추가 포인트' },
    { name: 'max_extra_point', label: '최대 추가 포인트' },
    { name: 'attack_power', label: '공격력' },
    { name: 'attack_power_by_lv', label: '레벨당 공격력' },
    { name: 'deffence', label: '방어력' },
    { name: 'deffence_by_lv', label: '레벨당 방어력' },
    { name: 'hp_regen', label: 'HP 재생' },
    { name: 'hp_regen_by_lv', label: '레벨당 HP 재생' },
    { name: 'sp_regen', label: 'SP 재생' },
    { name: 'sp_regen_by_lv', label: '레벨당 SP 재생' },
    { name: 'attack_speed', label: '공격 속도' },
    { name: 'attack_speed_limit', label: '최대 공격 속도' },
    { name: 'attack_speed_min', label: '최소 공격 속도' },
    { name: 'move_speed', label: '이동 속도' },
    { name: 'sight_range', label: '시야 범위' },
];

export default function CharacterEditPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: character, isLoading } = useQuery<Character>({
        queryKey: ['characters', id],
        queryFn: async () => {
            const response = await api.get(`/characters/${id}`);
            return response.data.data;
        },
    });

    const { data: allTags = [] } = useQuery<CharacterTag[]>({
        queryKey: ['character-tags'],
        queryFn: async () => {
            try {
                const response = await api.get('/character-tags');
                return response.data.data;
            } catch {
                return [];
            }
        },
    });

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<CharacterFormData>({
        resolver: zodResolver(characterSchema),
        values: character,
    });

    const mutation = useMutation({
        mutationFn: async (data: CharacterFormData) => {
            const response = await api.put(`/characters/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['characters'] });
            toast({
                title: '저장 완료',
                description: '캐릭터 정보가 저장되었습니다.',
            });
            navigate(-1);
        },
        onError: () => {
            toast({
                title: '저장 실패',
                description: '캐릭터 정보 저장에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

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
                title={`캐릭터 수정: ${character?.name || ''}`}
                description="캐릭터 스탯 정보를 수정합니다."
                showBack
            />

            {/* 태그 관리 섹션 */}
            <Card>
                <CardHeader>
                    <CardTitle>태그</CardTitle>
                </CardHeader>
                <CardContent>
                    {character && (
                        <CharacterTagsManager
                            characterId={character.id}
                            currentTags={character.tags || []}
                            allTags={allTags}
                        />
                    )}
                </CardContent>
            </Card>

            <form onSubmit={handleSubmit((data) => mutation.mutate(data))}>
                <Card>
                    <CardHeader>
                        <CardTitle>캐릭터 정보</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {fields.map((field) => (
                                <div key={field.name} className="space-y-2">
                                    <Label htmlFor={field.name}>{field.label}</Label>
                                    <Input
                                        id={field.name}
                                        type={field.name === 'name' ? 'text' : 'number'}
                                        step="any"
                                        {...register(field.name)}
                                    />
                                    {errors[field.name] && (
                                        <p className="text-sm text-[hsl(var(--destructive))]">
                                            {errors[field.name]?.message}
                                        </p>
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="mt-6 flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => navigate(-1)}
                            >
                                취소
                            </Button>
                            <Button type="submit" disabled={mutation.isPending}>
                                {mutation.isPending ? '저장 중...' : '저장'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </div>
    );
}
