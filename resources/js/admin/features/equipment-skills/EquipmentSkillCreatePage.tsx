import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { EquipmentSkillForm, SkillFormData } from './EquipmentSkillForm';
import { toast } from '@/hooks/useToast';

export default function EquipmentSkillCreatePage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: async (data: SkillFormData) => {
            const response = await api.post('/equipment-skills', data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['equipment-skills'] });
            toast({
                title: '생성 완료',
                description: '새 장비 스킬이 생성되었습니다.',
            });
            navigate('/equipment-skills');
        },
        onError: () => {
            toast({
                title: '생성 실패',
                description: '장비 스킬 생성에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    return (
        <div className="space-y-6">
            <PageHeader
                title="새 장비 스킬"
                description="새로운 장비 스킬을 생성합니다."
                backLink="/equipment-skills"
            />
            <EquipmentSkillForm
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate('/equipment-skills')}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
