import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil, Trash2, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { VersionHistory } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { DeleteDialog } from '@/components/shared/DeleteDialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from '@/hooks/useToast';

const statusColors: Record<string, 'default' | 'success' | 'secondary'> = {
    '진행중': 'success',
    '예정': 'default',
    '종료': 'secondary',
};

export default function VersionHistoryListPage() {
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const queryClient = useQueryClient();

    const { data: versions = [], isLoading } = useQuery<VersionHistory[]>({
        queryKey: ['version-histories'],
        queryFn: async () => {
            const response = await api.get('/version-histories');
            return response.data.data;
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            await api.delete(`/version-histories/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['version-histories'] });
            toast({
                title: '삭제 완료',
                description: '버전 히스토리가 삭제되었습니다.',
            });
            setDeleteId(null);
        },
        onError: () => {
            toast({
                title: '삭제 실패',
                description: '버전 히스토리 삭제에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const columns: ColumnDef<VersionHistory>[] = [
        {
            accessorKey: 'id',
            header: 'ID',
        },
        {
            accessorKey: 'version',
            header: '버전',
        },
        {
            accessorKey: 'start_date',
            header: '시작일',
            cell: ({ row }) => row.original.start_date?.slice(0, 10),
        },
        {
            accessorKey: 'end_date',
            header: '종료일',
            cell: ({ row }) => row.original.end_date?.slice(0, 10),
        },
        {
            accessorKey: 'status',
            header: '상태',
            cell: ({ row }) => {
                const status = row.original.status || '종료';
                return (
                    <Badge variant={statusColors[status] || 'secondary'}>
                        {status}
                    </Badge>
                );
            },
        },
        {
            id: 'actions',
            cell: ({ row }) => (
                <div className="flex items-center gap-1">
                    <Button variant="ghost" size="icon" asChild>
                        <Link to={`/version-histories/${row.original.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setDeleteId(row.original.id)}
                    >
                        <Trash2 className="h-4 w-4 text-[hsl(var(--destructive))]" />
                    </Button>
                </div>
            ),
        },
    ];

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
                title="버전 히스토리"
                description="게임 버전 및 패치노트를 관리합니다."
                actions={
                    <Button asChild>
                        <Link to="/version-histories/create">
                            <Plus className="mr-2 h-4 w-4" />
                            새 버전
                        </Link>
                    </Button>
                }
            />
            <DataTable columns={columns} data={versions} />

            <DeleteDialog
                open={deleteId !== null}
                onOpenChange={(open) => !open && setDeleteId(null)}
                onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
                title="버전 삭제"
                description="이 버전 히스토리를 삭제하시겠습니까? 연결된 패치노트도 모두 삭제됩니다."
                isDeleting={deleteMutation.isPending}
            />
        </div>
    );
}
