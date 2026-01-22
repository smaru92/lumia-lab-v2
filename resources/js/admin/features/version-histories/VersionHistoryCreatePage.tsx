import { useNavigate } from 'react-router-dom';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { PageHeader } from '@/components/shared/PageHeader';
import { VersionHistoryForm, VersionFormData } from './VersionHistoryForm';
import { toast } from '@/hooks/useToast';

export default function VersionHistoryCreatePage() {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const mutation = useMutation({
        mutationFn: async (data: VersionFormData) => {
            const response = await api.post('/version-histories', data);
            return response.data;
        },
        onSuccess: (data) => {
            queryClient.invalidateQueries({ queryKey: ['version-histories'] });
            toast({
                title: '생성 완료',
                description: '새 버전 히스토리가 생성되었습니다.',
            });
            navigate(`/version-histories/${data.data.id}/edit`);
        },
        onError: () => {
            toast({
                title: '생성 실패',
                description: '버전 히스토리 생성에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    return (
        <div className="space-y-6">
            <PageHeader
                title="새 버전"
                description="새로운 버전 히스토리를 생성합니다."
                showBack
            />
            <VersionHistoryForm
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate(-1)}
                isSubmitting={mutation.isPending}
            />
        </div>
    );
}
