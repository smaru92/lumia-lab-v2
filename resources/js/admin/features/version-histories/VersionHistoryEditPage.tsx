import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import api from '@/lib/axios';
import { VersionHistory } from '@/types';
import { PageHeader } from '@/components/shared/PageHeader';
import { VersionHistoryForm, VersionFormData } from './VersionHistoryForm';
import { PatchNotesPanel } from './PatchNotesPanel';
import { toast } from '@/hooks/useToast';

export default function VersionHistoryEditPage() {
    const { id } = useParams<{ id: string }>();
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    const { data: version, isLoading } = useQuery<VersionHistory>({
        queryKey: ['version-histories', id],
        queryFn: async () => {
            const response = await api.get(`/version-histories/${id}`);
            return response.data.data;
        },
    });

    const mutation = useMutation({
        mutationFn: async (data: VersionFormData) => {
            const response = await api.put(`/version-histories/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['version-histories'] });
            toast({
                title: '저장 완료',
                description: '버전 히스토리가 저장되었습니다.',
            });
        },
        onError: () => {
            toast({
                title: '저장 실패',
                description: '버전 히스토리 저장에 실패했습니다.',
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
                title={`버전 수정: ${version?.version || ''}`}
                description="버전 히스토리 및 패치노트를 수정합니다."
                backLink="/version-histories"
            />
            <VersionHistoryForm
                version={version}
                onSubmit={(data) => mutation.mutate(data)}
                onCancel={() => navigate('/version-histories')}
                isSubmitting={mutation.isPending}
            />
            <PatchNotesPanel versionHistoryId={Number(id)} />
        </div>
    );
}
