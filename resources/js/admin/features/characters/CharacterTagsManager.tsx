import { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { X, Plus } from 'lucide-react';
import api from '@/lib/axios';
import { CharacterTag } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { toast } from '@/hooks/useToast';

interface CharacterTagsManagerProps {
    characterId: number;
    currentTags: CharacterTag[];
    allTags: CharacterTag[];
}

export function CharacterTagsManager({
    characterId,
    currentTags,
    allTags,
}: CharacterTagsManagerProps) {
    const [selectedTagId, setSelectedTagId] = useState<string>('');
    const [newTagName, setNewTagName] = useState<string>('');
    const queryClient = useQueryClient();

    const availableTags = allTags.filter(
        (tag) => !currentTags.some((ct) => ct.id === tag.id)
    );

    const syncMutation = useMutation({
        mutationFn: async (params: { tagIds: number[]; newTags?: string[] }) => {
            const response = await api.post(`/characters/${characterId}/tags`, {
                tag_ids: params.tagIds,
                new_tags: params.newTags,
            });
            return response.data;
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['characters', String(characterId)] });
            queryClient.invalidateQueries({ queryKey: ['characters'] });
            queryClient.invalidateQueries({ queryKey: ['character-tags'] });
            toast({
                title: '태그 업데이트',
                description: '캐릭터 태그가 업데이트되었습니다.',
            });
        },
        onError: () => {
            toast({
                title: '실패',
                description: '태그 업데이트에 실패했습니다.',
                variant: 'destructive',
            });
        },
    });

    const handleAddExistingTag = () => {
        if (!selectedTagId) return;
        const newTagIds = [...currentTags.map((t) => t.id), Number(selectedTagId)];
        syncMutation.mutate({ tagIds: newTagIds });
        setSelectedTagId('');
    };

    const handleAddNewTag = () => {
        const trimmedName = newTagName.trim();
        if (!trimmedName) return;

        // Check if tag already exists
        const existingTag = allTags.find(
            (t) => t.name.toLowerCase() === trimmedName.toLowerCase()
        );

        if (existingTag) {
            // If tag exists, just add it
            const isAlreadyAdded = currentTags.some((ct) => ct.id === existingTag.id);
            if (isAlreadyAdded) {
                toast({
                    title: '알림',
                    description: '이미 추가된 태그입니다.',
                });
                setNewTagName('');
                return;
            }
            const newTagIds = [...currentTags.map((t) => t.id), existingTag.id];
            syncMutation.mutate({ tagIds: newTagIds });
        } else {
            // Create new tag
            syncMutation.mutate({
                tagIds: currentTags.map((t) => t.id),
                newTags: [trimmedName],
            });
        }
        setNewTagName('');
    };

    const handleRemoveTag = (tagId: number) => {
        const newTagIds = currentTags.filter((t) => t.id !== tagId).map((t) => t.id);
        syncMutation.mutate({ tagIds: newTagIds });
    };

    const handleKeyPress = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            handleAddNewTag();
        }
    };

    return (
        <div className="space-y-4">
            <div className="flex flex-col gap-3">
                {/* 기존 태그 선택 */}
                <div className="flex items-center gap-2">
                    <Select value={selectedTagId} onValueChange={setSelectedTagId}>
                        <SelectTrigger className="w-[200px]">
                            <SelectValue placeholder="기존 태그 선택..." />
                        </SelectTrigger>
                        <SelectContent>
                            {availableTags.length === 0 ? (
                                <div className="px-2 py-4 text-center text-sm text-[hsl(var(--muted-foreground))]">
                                    추가 가능한 태그가 없습니다.
                                </div>
                            ) : (
                                availableTags.map((tag) => (
                                    <SelectItem key={tag.id} value={String(tag.id)}>
                                        {tag.name}
                                    </SelectItem>
                                ))
                            )}
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleAddExistingTag}
                        disabled={!selectedTagId || syncMutation.isPending}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        추가
                    </Button>
                </div>

                {/* 새 태그 입력 */}
                <div className="flex items-center gap-2">
                    <Input
                        placeholder="새 태그 입력..."
                        value={newTagName}
                        onChange={(e) => setNewTagName(e.target.value)}
                        onKeyPress={handleKeyPress}
                        className="w-[200px]"
                    />
                    <Button
                        type="button"
                        onClick={handleAddNewTag}
                        disabled={!newTagName.trim() || syncMutation.isPending}
                    >
                        <Plus className="mr-2 h-4 w-4" />
                        새 태그 생성
                    </Button>
                </div>
            </div>

            {/* 현재 태그 목록 */}
            <div className="flex flex-wrap gap-2">
                {currentTags.length === 0 ? (
                    <p className="text-sm text-[hsl(var(--muted-foreground))]">
                        등록된 태그가 없습니다.
                    </p>
                ) : (
                    currentTags.map((tag) => (
                        <Badge
                            key={tag.id}
                            variant="secondary"
                            className="flex items-center gap-1 px-3 py-1"
                        >
                            {tag.name}
                            <button
                                type="button"
                                onClick={() => handleRemoveTag(tag.id)}
                                className="ml-1 hover:text-[hsl(var(--destructive))]"
                                disabled={syncMutation.isPending}
                            >
                                <X className="h-3 w-3" />
                            </button>
                        </Badge>
                    ))
                )}
            </div>
        </div>
    );
}
