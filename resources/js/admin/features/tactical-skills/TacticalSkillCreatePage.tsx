import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { TacticalSkillForm, TacticalSkillFormData } from './TacticalSkillForm';
import { toast } from '@/hooks/useToast';

export default function TacticalSkillCreatePage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: async (data: TacticalSkillFormData) => {
            const response = await api.post('/tactical-skills', data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['tactical-skills'] });
            toast({ title: '생성 완료', description: '새 전술스킬이 생성되었습니다.' });
            navigate(-1);
        },
        onError: () => {
            toast({ title: '생성 실패', description: '전술스킬 생성에 실패했습니다.', variant: 'destructive' });
        },
    });

    return (
        <div className="space-y-6">
            <PageHeader title="새 전술스킬" description="새로운 전술스킬을 생성합니다." showBack />
            <TacticalSkillForm
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate(-1)}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
