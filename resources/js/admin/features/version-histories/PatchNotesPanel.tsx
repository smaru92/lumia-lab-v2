import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Plus, Pencil, Trash2 } from 'lucide-react';
import api from '@/lib/axios';
import { PatchNote, Character, Equipment, SelectOption } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { DeleteDialog } from '@/components/shared/DeleteDialog';
import { PatchNoteDialog } from './PatchNoteDialog';
import { toast } from '@/hooks/useToast';

const categoryColors: Record<string, 'info' | 'warning' | 'success' | 'secondary' | 'default'> = {
    '캐릭터': 'info',
    '아이템': 'warning',
    '특성': 'success',
    '시스템': 'secondary',
    '전술스킬': 'default',
    '기타': 'secondary',
};

const patchTypeColors: Record<string, 'buff' | 'nerf' | 'warning' | 'info' | 'secondary' | 'success' | 'destructive'> = {
    '버프': 'buff',
    '너프': 'nerf',
    '조정': 'warning',
    '리워크': 'info',
    '신규': 'success',
    '삭제': 'destructive',
};

interface PatchNotesPanelProps {
    versionHistoryId: number;
}

export function PatchNotesPanel({ versionHistoryId }: PatchNotesPanelProps) {
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editingNote, setEditingNote] = useState<PatchNote | null>(null);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const queryClient = useQueryClient();

    const { data: patchNotes = [], isLoading } = useQuery<PatchNote[]>({
        queryKey: ['patch-notes', versionHistoryId],
        queryFn: async () => {
            const response = await api.get(`/version-histories/${versionHistoryId}/patch-notes`);
            return response.data.data;
        },
    });

    const { data: characters = [] } = useQuery<Character[]>({
        queryKey: ['characters-options'],
        queryFn: async () => {
            const response = await api.get('/characters');
            return response.data.data;
        },
    });

    const { data: equipment = [] } = useQuery<Equipment[]>({
        queryKey: ['equipment-options'],
        queryFn: async () => {
            const response = await api.get('/equipment');
            return response.data.data;
        },
    });

    const { data: traits = [] } = useQuery<SelectOption[]>({
        queryKey: ['traits-options'],
        queryFn: async () => {
            const response = await api.get('/traits');
            return response.data.data;
        },
    });

    const { data: tacticalSkills = [] } = useQuery<SelectOption[]>({
        queryKey: ['tactical-skills-options'],
        queryFn: async () => {
            const response = await api.get('/tactical-skills');
            return response.data.data;
        },
    });

    const createMutation = useMutation({
        mutationFn: async (data: Partial<PatchNote>) => {
            const response = await api.post(`/version-histories/${versionHistoryId}/patch-notes`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['patch-notes', versionHistoryId] });
            toast({ title: '생성 완료', description: '패치노트가 추가되었습니다.' });
            setDialogOpen(false);
        },
        onError: () => {
            toast({ title: '실패', description: '패치노트 추가에 실패했습니다.', variant: 'destructive' });
        },
    });

    const updateMutation = useMutation({
        mutationFn: async ({ id, data }: { id: number; data: Partial<PatchNote> }) => {
            const response = await api.put(`/patch-notes/${id}`, data);
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['patch-notes', versionHistoryId] });
            toast({ title: '저장 완료', description: '패치노트가 저장되었습니다.' });
            setDialogOpen(false);
            setEditingNote(null);
        },
        onError: () => {
            toast({ title: '실패', description: '패치노트 저장에 실패했습니다.', variant: 'destructive' });
        },
    });

    const deleteMutation = useMutation({
        mutationFn: async (id: number) => {
            await api.delete(`/patch-notes/${id}`);
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['patch-notes', versionHistoryId] });
            toast({ title: '삭제 완료', description: '패치노트가 삭제되었습니다.' });
            setDeleteId(null);
        },
        onError: () => {
            toast({ title: '실패', description: '패치노트 삭제에 실패했습니다.', variant: 'destructive' });
        },
    });

    const handleEdit = (note: PatchNote) => {
        setEditingNote(note);
        setDialogOpen(true);
    };

    const handleSubmit = (data: Partial<PatchNote>) => {
        if (editingNote) {
            updateMutation.mutate({ id: editingNote.id, data });
        } else {
            createMutation.mutate(data);
        }
    };

    const handleDialogClose = () => {
        setDialogOpen(false);
        setEditingNote(null);
    };

    if (isLoading) {
        return (
            <Card>
                <CardContent className="flex h-32 items-center justify-center">
                    <div className="h-6 w-6 animate-spin rounded-full border-4 border-primary border-t-transparent" />
                </CardContent>
            </Card>
        );
    }

    return (
        <>
            <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                    <CardTitle>패치노트</CardTitle>
                    <Button onClick={() => setDialogOpen(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        추가
                    </Button>
                </CardHeader>
                <CardContent>
                    {patchNotes.length === 0 ? (
                        <p className="text-center text-sm text-[hsl(var(--muted-foreground))]">
                            패치노트가 없습니다.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>구분</TableHead>
                                    <TableHead>대상</TableHead>
                                    <TableHead>무기</TableHead>
                                    <TableHead>스킬</TableHead>
                                    <TableHead>패치 유형</TableHead>
                                    <TableHead>내용</TableHead>
                                    <TableHead className="w-[100px]">액션</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {patchNotes.map((note) => (
                                    <TableRow key={note.id}>
                                        <TableCell>
                                            <Badge variant={categoryColors[note.category] || 'secondary'}>
                                                {note.category}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{note.target_name || '-'}</TableCell>
                                        <TableCell>{note.weapon_type || '-'}</TableCell>
                                        <TableCell>{note.skill_type || '-'}</TableCell>
                                        <TableCell>
                                            <Badge variant={patchTypeColors[note.patch_type] || 'secondary'}>
                                                {note.patch_type}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="max-w-xs truncate">
                                            {note.content.slice(0, 100)}
                                            {note.content.length > 100 && '...'}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleEdit(note)}
                                                >
                                                    <Pencil className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => setDeleteId(note.id)}
                                                >
                                                    <Trash2 className="h-4 w-4 text-[hsl(var(--destructive))]" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>

            <PatchNoteDialog
                open={dialogOpen}
                onOpenChange={handleDialogClose}
                patchNote={editingNote}
                onSubmit={handleSubmit}
                isSubmitting={createMutation.isPending || updateMutation.isPending}
                characters={characters}
                equipment={equipment}
                traits={traits}
                tacticalSkills={tacticalSkills}
            />

            <DeleteDialog
                open={deleteId !== null}
                onOpenChange={(open) => !open && setDeleteId(null)}
                onConfirm={() => deleteId && deleteMutation.mutate(deleteId)}
                title="패치노트 삭제"
                description="이 패치노트를 삭제하시겠습니까?"
                isDeleting={deleteMutation.isPending}
            />
        </>
    );
}
