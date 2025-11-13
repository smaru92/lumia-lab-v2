<?php

namespace App\Filament\Resources\Equipment\Pages;

use App\Filament\Resources\Equipment\EquipmentResource;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class ListEquipment extends ListRecords
{
    protected static string $resource = EquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('정보 갱신')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('장비 정보 갱신')
                ->modalDescription('ER API에서 최신 장비 정보를 가져옵니다.')
                ->modalSubmitActionLabel('갱신')
                ->action(function () {
                    try {
                        $response = Http::get(route('api.equipment'));

                        if ($response->successful()) {
                            Notification::make()
                                ->title('장비 정보 갱신 완료')
                                ->success()
                                ->send();
                        } else {
                            throw new \Exception('API 요청 실패');
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('장비 정보 갱신 실패')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
