import { Routes, Route, Navigate } from 'react-router-dom';
import { useAuth } from '@/hooks/useAuth';
import AdminLayout from '@/components/layout/AdminLayout';
import LoginPage from '@/features/auth/LoginPage';
import DashboardPage from '@/features/dashboard/DashboardPage';
import CharacterListPage from '@/features/characters/CharacterListPage';
import CharacterEditPage from '@/features/characters/CharacterEditPage';
import EquipmentListPage from '@/features/equipment/EquipmentListPage';
import EquipmentEditPage from '@/features/equipment/EquipmentEditPage';
import EquipmentSkillListPage from '@/features/equipment-skills/EquipmentSkillListPage';
import EquipmentSkillCreatePage from '@/features/equipment-skills/EquipmentSkillCreatePage';
import EquipmentSkillEditPage from '@/features/equipment-skills/EquipmentSkillEditPage';
import VersionHistoryListPage from '@/features/version-histories/VersionHistoryListPage';
import VersionHistoryCreatePage from '@/features/version-histories/VersionHistoryCreatePage';
import VersionHistoryEditPage from '@/features/version-histories/VersionHistoryEditPage';

function ProtectedRoute({ children }: { children: React.ReactNode }) {
    const { user, isLoading } = useAuth();

    if (isLoading) {
        return (
            <div className="flex h-screen items-center justify-center">
                <div className="h-8 w-8 animate-spin rounded-full border-4 border-primary border-t-transparent" />
            </div>
        );
    }

    if (!user) {
        return <Navigate to="/login" replace />;
    }

    return <>{children}</>;
}

export default function App() {
    return (
        <Routes>
            <Route path="/login" element={<LoginPage />} />
            <Route
                path="/*"
                element={
                    <ProtectedRoute>
                        <AdminLayout>
                            <Routes>
                                <Route path="/" element={<DashboardPage />} />
                                <Route path="/characters" element={<CharacterListPage />} />
                                <Route path="/characters/:id/edit" element={<CharacterEditPage />} />
                                <Route path="/equipment" element={<EquipmentListPage />} />
                                <Route path="/equipment/:id/edit" element={<EquipmentEditPage />} />
                                <Route path="/equipment-skills" element={<EquipmentSkillListPage />} />
                                <Route path="/equipment-skills/create" element={<EquipmentSkillCreatePage />} />
                                <Route path="/equipment-skills/:id/edit" element={<EquipmentSkillEditPage />} />
                                <Route path="/version-histories" element={<VersionHistoryListPage />} />
                                <Route path="/version-histories/create" element={<VersionHistoryCreatePage />} />
                                <Route path="/version-histories/:id/edit" element={<VersionHistoryEditPage />} />
                            </Routes>
                        </AdminLayout>
                    </ProtectedRoute>
                }
            />
        </Routes>
    );
}
