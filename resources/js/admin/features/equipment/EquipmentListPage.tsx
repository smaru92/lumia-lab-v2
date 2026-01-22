import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import api from '@/lib/axios';
import { Equipment } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const gradeColors: Record<string, 'default' | 'secondary' | 'warning' | 'info' | 'success' | 'destructive'> = {
    Common: 'secondary',
    Uncommon: 'success',
    Rare: 'info',
    Epic: 'warning',
    Legendary: 'destructive',
};

const columns: ColumnDef<Equipment>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
    },
    {
        accessorKey: 'name',
        header: '이름',
    },
    {
        accessorKey: 'item_type1',
        header: '타입1',
    },
    {
        accessorKey: 'item_type2',
        header: '타입2',
    },
    {
        accessorKey: 'item_grade',
        header: '등급',
        cell: ({ row }) => {
            const grade = row.original.item_grade;
            if (!grade) return '-';
            return (
                <Badge variant={gradeColors[grade] || 'default'}>
                    {grade}
                </Badge>
            );
        },
    },
    {
        accessorKey: 'attack_power',
        header: '공격력',
    },
    {
        accessorKey: 'defense',
        header: '방어력',
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <Button variant="ghost" size="icon" asChild>
                <Link to={`/equipment/${row.original.id}/edit`}>
                    <Pencil className="h-4 w-4" />
                </Link>
            </Button>
        ),
    },
];

export default function EquipmentListPage() {
    const { data: equipment = [], isLoading } = useQuery<Equipment[]>({
        queryKey: ['equipment'],
        queryFn: async () => {
            const response = await api.get('/equipment');
            return response.data.data;
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
                title="장비"
                description="장비 아이템 목록을 관리합니다."
            />
            <DataTable
                columns={columns}
                data={equipment}
                searchKey="name"
                searchPlaceholder="장비 이름으로 검색..."
            />
        </div>
    );
}
