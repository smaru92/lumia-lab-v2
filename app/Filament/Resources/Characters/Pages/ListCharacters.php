<?php

namespace App\Filament\Resources\Characters\Pages;

use App\Filament\Resources\Characters\CharacterResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class ListCharacters extends ListRecords
{
    protected static string $resource = CharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('정보 갱신')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('캐릭터 정보 갱신')
                ->modalDescription('ER API에서 최신 캐릭터 정보를 가져옵니다.')
                ->modalSubmitActionLabel('갱신')
                ->action(function () {
                    try {
                        $response = Http::get(route('api.character'));

                        if ($response->successful()) {
                            Notification::make()
                                ->title('캐릭터 정보 갱신 완료')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('API 요청 실패');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('캐릭터 정보 갱신 실패')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
