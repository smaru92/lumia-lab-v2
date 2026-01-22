import { useQuery } from '@tanstack/react-query';
import { Link } from 'react-router-dom';
import { ColumnDef } from '@tanstack/react-table';
import { Pencil } from 'lucide-react';
import api from '@/lib/axios';
import { Character } from '@/types';
import { DataTable } from '@/components/shared/DataTable';
import { PageHeader } from '@/components/shared/PageHeader';
import { Button } from '@/components/ui/button';

const columns: ColumnDef<Character>[] = [
    {
        accessorKey: 'id',
        header: 'ID',
    },
    {
        accessorKey: 'name',
        header: '이름',
    },
    {
        accessorKey: 'max_hp',
        header: '최대 HP',
    },
    {
        accessorKey: 'attack_power',
        header: '공격력',
    },
    {
        accessorKey: 'deffence',
        header: '방어력',
    },
    {
        accessorKey: 'move_speed',
        header: '이동속도',
    },
    {
        id: 'actions',
        cell: ({ row }) => (
            <Button variant="ghost" size="icon" asChild>
                <Link to={`/characters/${row.original.id}/edit`}>
                    <Pencil className="h-4 w-4" />
                </Link>
            </Button>
        ),
    },
];

export default function CharacterListPage() {
    const { data: characters = [], isLoading } = useQuery<Character[]>({
        queryKey: ['characters'],
        queryFn: async () => {
            const response = await api.get('/characters');
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
                title="캐릭터"
                description="게임 캐릭터 목록을 관리합니다."
            />
            <DataTable
                columns={columns}
                data={characters}
                searchKey="name"
                searchPlaceholder="캐릭터 이름으로 검색..."
            />
        </div>
    );
}
