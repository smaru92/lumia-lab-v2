import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import api from '@/lib/axios';
import { Equipment, EquipmentSkill } from '@/types';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { toast } from '@/hooks/useToast';
import { EquipmentSkillsManager } from './EquipmentSkillsManager';

const equipmentSchema = z.object({
    name: z.string().nullable(),
    item_type1: z.string().nullable(),
    item_type2: z.string().nullable(),
    item_grade: z.string().nullable(),
    attack_power: z.coerce.number().nullable(),
    attack_power_by_lv: z.coerce.number().nullable(),
    defense: z.coerce.number().nullable(),
    defense_by_lv: z.coerce.number().nullable(),
    skill_amp: z.coerce.number().nullable(),
    skill_amp_by_level: z.coerce.number().nullable(),
    skill_amp_ratio: z.coerce.number().nullable(),
    skill_amp_ratio_by_level: z.coerce.number().nullable(),
    adaptive_force: z.coerce.number().nullable(),
    adaptive_force_by_level: z.coerce.number().nullable(),
    max_hp: z.coerce.number().nullable(),
    max_hp_by_lv: z.coerce.number().nullable(),
    max_sp: z.coerce.number().nullable(),
    max_sp_by_lv: z.coerce.number().nullable(),
    hp_regen: z.coerce.number().nullable(),
    hp_regen_ratio: z.coerce.number().nullable(),
    sp_regen: z.coerce.number().nullable(),
    sp_regen_ratio: z.coerce.number().nullable(),
    attack_speed_ratio: z.coerce.number().nullable(),
    attack_speed_ratio_by_lv: z.coerce.number().nullable(),
    critical_strike_chance: z.coerce.number().nullable(),
    critical_strike_damage: z.coerce.number().nullable(),
    prevent_critical_strike_damaged: z.coerce.number().nullable(),
    cooldown_reduction: z.coerce.number().nullable(),
    cooldown_limit: z.coerce.number().nullable(),
    life_steal: z.coerce.number().nullable(),
    normal_life_steal: z.coerce.number().nullable(),
    skill_life_steal: z.coerce.number().nullable(),
    move_speed: z.coerce.number().nullable(),
    move_speed_ratio: z.coerce.number().nullable(),
    move_speed_out_of_combat: z.coerce.number().nullable(),
    sight_range: z.coerce.number().nullable(),
    attack_range: z.coerce.number().nullable(),
    increase_basic_attack_damage: z.coerce.number().nullable(),
    increase_basic_attack_damage_by_lv: z.coerce.number().nullable(),
    increase_basic_attack_damage_ratio: z.coerce.number().nullable(),
    increase_basic_attack_damage_ratio_by_lv: z.coerce.number().nullable(),
    prevent_basic_attack_damaged: z.coerce.number().nullable(),
    prevent_basic_attack_damaged_by_lv: z.coerce.number().nullable(),
    prevent_basic_attack_damaged_ratio: z.coerce.number().nullable(),
    prevent_basic_attack_damaged_ratio_by_lv: z.coerce.number().nullable(),
    prevent_skill_damaged: z.coerce.number().nullable(),
    prevent_skill_damaged_by_lv: z.coerce.number().nullable(),
    prevent_skill_damaged_ratio: z.coerce.number().nullable(),
    prevent_skill_damaged_ratio_by_lv: z.coerce.number().nullable(),
    penetration_defense: z.coerce.number().nullable(),
    penetration_defense_ratio: z.coerce.number().nullable(),
    trap_damage_reduce: z.coerce.number().nullable(),
    trap_damage_reduce_ratio: z.coerce.number().nullable(),
    slow_resist_ratio: z.coerce.number().nullable(),
    hp_healed_increase_ratio: z.coerce.number().nullable(),
    healer_give_hp_heal_ratio: z.coerce.number().nullable(),
    unique_attack_range: z.coerce.number().nullable(),
    unique_hp_healed_increase_ratio: z.coerce.number().nullable(),
    unique_cooldown_limit: z.coerce.number().nullable(),
    unique_tenacity: z.coerce.number().nullable(),
    unique_move_speed: z.coerce.number().nullable(),
    unique_penetration_defense: z.coerce.number().nullable(),
    unique_penetration_defense_ratio: z.coerce.number().nullable(),
    unique_life_steal: z.coerce.number().nullable(),
    unique_skill_amp_ratio: z.coerce.number().nullable(),
});

type EquipmentFormData = z.infer<typeof equipmentSchema>;

const basicFields = [
    { name: 'name', label: '이름', type: 'text' },
    { name: 'item_type1', label: '타입1', type: 'text' },
    { name: 'item_type2', label: '타입2', type: 'text' },
    { name: 'item_grade', label: '등급', type: 'text' },
] as const;

const offenseFields = [
    { name: 'attack_power', label: '공격력' },
    { name: 'attack_power_by_lv', label: '레벨당 공격력' },
    { name: 'skill_amp', label: '스킬 증폭' },
    { name: 'skill_amp_by_level', label: '레벨당 스킬 증폭' },
    { name: 'skill_amp_ratio', label: '스킬 증폭 비율' },
    { name: 'skill_amp_ratio_by_level', label: '레벨당 스킬 증폭 비율' },
    { name: 'adaptive_force', label: '적응형 능력치' },
    { name: 'adaptive_force_by_level', label: '레벨당 적응형 능력치' },
    { name: 'attack_speed_ratio', label: '공격 속도 비율' },
    { name: 'attack_speed_ratio_by_lv', label: '레벨당 공격 속도 비율' },
    { name: 'critical_strike_chance', label: '치명타 확률' },
    { name: 'critical_strike_damage', label: '치명타 피해' },
    { name: 'penetration_defense', label: '방어 관통' },
    { name: 'penetration_defense_ratio', label: '방어 관통 비율' },
    { name: 'increase_basic_attack_damage', label: '기본 공격 피해 증가' },
    { name: 'increase_basic_attack_damage_by_lv', label: '레벨당 기본 공격 피해 증가' },
    { name: 'increase_basic_attack_damage_ratio', label: '기본 공격 피해 증가 비율' },
    { name: 'increase_basic_attack_damage_ratio_by_lv', label: '레벨당 기본 공격 피해 증가 비율' },
] as const;

const defenseFields = [
    { name: 'defense', label: '방어력' },
    { name: 'defense_by_lv', label: '레벨당 방어력' },
    { name: 'max_hp', label: '최대 HP' },
    { name: 'max_hp_by_lv', label: '레벨당 최대 HP' },
    { name: 'max_sp', label: '최대 SP' },
    { name: 'max_sp_by_lv', label: '레벨당 최대 SP' },
    { name: 'hp_regen', label: 'HP 재생' },
    { name: 'hp_regen_ratio', label: 'HP 재생 비율' },
    { name: 'sp_regen', label: 'SP 재생' },
    { name: 'sp_regen_ratio', label: 'SP 재생 비율' },
    { name: 'prevent_critical_strike_damaged', label: '치명타 피해 감소' },
    { name: 'prevent_basic_attack_damaged', label: '기본 공격 피해 감소' },
    { name: 'prevent_basic_attack_damaged_by_lv', label: '레벨당 기본 공격 피해 감소' },
    { name: 'prevent_basic_attack_damaged_ratio', label: '기본 공격 피해 감소 비율' },
    { name: 'prevent_basic_attack_damaged_ratio_by_lv', label: '레벨당 기본 공격 피해 감소 비율' },
    { name: 'prevent_skill_damaged', label: '스킬 피해 감소' },
    { name: 'prevent_skill_damaged_by_lv', label: '레벨당 스킬 피해 감소' },
    { name: 'prevent_skill_damaged_ratio', label: '스킬 피해 감소 비율' },
    { name: 'prevent_skill_damaged_ratio_by_lv', label: '레벨당 스킬 피해 감소 비율' },
    { name: 'trap_damage_reduce', label: '트랩 피해 감소' },
    { name: 'trap_damage_reduce_ratio', label: '트랩 피해 감소 비율' },
] as const;

const utilityFields = [
    { name: 'cooldown_reduction', label: '쿨다운 감소' },
    { name: 'cooldown_limit', label: '쿨다운 한계' },
    { name: 'life_steal', label: '생명력 흡수' },
    { name: 'normal_life_steal', label: '일반 생명력 흡수' },
    { name: 'skill_life_steal', label: '스킬 생명력 흡수' },
    { name: 'move_speed', label: '이동 속도' },
    { name: 'move_speed_ratio', label: '이동 속도 비율' },
    { name: 'move_speed_out_of_combat', label: '비전투 이동 속도' },
    { name: 'sight_range', label: '시야 범위' },
    { name: 'attack_range', label: '공격 범위' },
    { name: 'slow_resist_ratio', label: '둔화 저항 비율' },
    { name: 'hp_healed_increase_ratio', label: 'HP 회복 증가 비율' },
    { name: 'healer_give_hp_heal_ratio', label: '힐러 HP 회복 비율' },
] as const;

const uniqueFields = [
    { name: 'unique_attack_range', label: '고유 공격 범위' },
    { name: 'unique_hp_healed_increase_ratio', label: '고유 HP 회복 증가 비율' },
    { name: 'unique_cooldown_limit', label: '고유 쿨다운 한계' },
    { name: 'unique_tenacity', label: '고유 강인함' },
    { name: 'unique_move_speed', label: '고유 이동 속도' },
    { name: 'unique_penetration_defense', label: '고유 방어 관통' },
    { name: 'unique_penetration_defense_ratio', label: '고유 방어 관통 비율' },
    { name: 'unique_life_steal', label: '고유 생명력 흡수' },
    { name: 'unique_skill_amp_ratio', label: '고유 스킬 증폭 비율' },
] as const;

export default function EquipmentEditPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: equipment, isLoading } = useQuery<Equipment>({
        queryKey: ['equipment', id],
        queryFn: async () => {
            const response = await api.get(`/equipment/${id}`);
            return response.data.data;
        },
    });

    const { data: allSkills = [] } = useQuery<EquipmentSkill[]>({
        queryKey: ['equipment-skills'],
        queryFn: async () => {
            const response = await api.get('/equipment-skills');
            return response.data.data;
        },
    });

    const {
        register,
        handleSubmit,
        formState: { errors },
    } = useForm<EquipmentFormData>({
        resolver: zodResolver(equipmentSchema),
        values: equipment,
    });

    const mutation = useMutation({
        mutationFn: async (data: EquipmentFormData) => {
            const response = await api.put(`/equipment/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['equipment'] });
            toast({
                title: '저장 완료',
                description: '장비 정보가 저장되었습니다.',
            });
            navigate(-1);
        },
        onError: () => {
            toast({
                title: '저장 실패',
                description: '장비 정보 저장에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const renderFields = (fields: readonly { name: string; label: string; type?: string }[]) => (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {fields.map((field) => (
                <div key={field.name} className="space-y-2">
                    <Label htmlFor={field.name}>{field.label}</Label>
                    <Input
                        id={field.name}
                        type={field.type || 'number'}
                        step="any"
                        {...register(field.name as keyof EquipmentFormData)}
                    />
                    {errors[field.name as keyof EquipmentFormData] && (
                        <p className="text-sm text-[hsl(var(--destructive))]">
                            {errors[field.name as keyof EquipmentFormData]?.message}
                        </p>
                    )}
                </div>
            ))}
        </div>
    );

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
                title={`장비 수정: ${equipment?.name || ''}`}
                description="장비 아이템 정보를 수정합니다."
                showBack
            />

            <form onSubmit={handleSubmit((data) => mutation.mutate(data))}>
                <Card>
                    <CardHeader>
                        <CardTitle>장비 정보</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Tabs defaultValue="basic" className="w-full">
                            <TabsList className="mb-4">
                                <TabsTrigger value="basic">기본 정보</TabsTrigger>
                                <TabsTrigger value="offense">공격</TabsTrigger>
                                <TabsTrigger value="defense">방어</TabsTrigger>
                                <TabsTrigger value="utility">유틸리티</TabsTrigger>
                                <TabsTrigger value="unique">고유</TabsTrigger>
                                <TabsTrigger value="skills">스킬</TabsTrigger>
                            </TabsList>

                            <TabsContent value="basic">{renderFields(basicFields)}</TabsContent>
                            <TabsContent value="offense">{renderFields(offenseFields)}</TabsContent>
                            <TabsContent value="defense">{renderFields(defenseFields)}</TabsContent>
                            <TabsContent value="utility">{renderFields(utilityFields)}</TabsContent>
                            <TabsContent value="unique">{renderFields(uniqueFields)}</TabsContent>
                            <TabsContent value="skills">
                                <EquipmentSkillsManager
                                    equipmentId={Number(id)}
                                    currentSkills={equipment?.equipment_skills || []}
                                    allSkills={allSkills}
                                />
                            </TabsContent>
                        </Tabs>

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
