export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export interface CharacterTag {
    id: number;
    name: string;
}

export interface Character {
    id: number;
    name: string;
    max_hp: number | null;
    max_hp_by_lv: number | null;
    max_mp: number | null;
    max_mp_by_lv: number | null;
    init_extra_point: number | null;
    max_extra_point: number | null;
    attack_power: number | null;
    attack_power_by_lv: number | null;
    deffence: number | null;
    deffence_by_lv: number | null;
    hp_regen: number | null;
    hp_regen_by_lv: number | null;
    sp_regen: number | null;
    sp_regen_by_lv: number | null;
    attack_speed: number | null;
    attack_speed_limit: number | null;
    attack_speed_min: number | null;
    move_speed: number | null;
    sight_range: number | null;
    created_at: string;
    updated_at: string;
    tags?: CharacterTag[];
}

export interface Equipment {
    id: number;
    name: string | null;
    item_type1: string | null;
    item_type2: string | null;
    item_type3: string | null;
    item_grade: string | null;
    attack_power: number | null;
    attack_power_by_lv: number | null;
    defense: number | null;
    defense_by_lv: number | null;
    skill_amp: number | null;
    skill_amp_by_level: number | null;
    skill_amp_ratio: number | null;
    skill_amp_ratio_by_level: number | null;
    adaptive_force: number | null;
    adaptive_force_by_level: number | null;
    max_hp: number | null;
    max_hp_by_lv: number | null;
    max_sp: number | null;
    max_sp_by_lv: number | null;
    hp_regen: number | null;
    hp_regen_ratio: number | null;
    sp_regen: number | null;
    sp_regen_ratio: number | null;
    attack_speed_ratio: number | null;
    attack_speed_ratio_by_lv: number | null;
    critical_strike_chance: number | null;
    critical_strike_damage: number | null;
    prevent_critical_strike_damaged: number | null;
    cooldown_reduction: number | null;
    cooldown_limit: number | null;
    life_steal: number | null;
    normal_life_steal: number | null;
    skill_life_steal: number | null;
    move_speed: number | null;
    move_speed_ratio: number | null;
    move_speed_out_of_combat: number | null;
    sight_range: number | null;
    attack_range: number | null;
    increase_basic_attack_damage: number | null;
    increase_basic_attack_damage_by_lv: number | null;
    increase_basic_attack_damage_ratio: number | null;
    increase_basic_attack_damage_ratio_by_lv: number | null;
    prevent_basic_attack_damaged: number | null;
    prevent_basic_attack_damaged_by_lv: number | null;
    prevent_basic_attack_damaged_ratio: number | null;
    prevent_basic_attack_damaged_ratio_by_lv: number | null;
    prevent_skill_damaged: number | null;
    prevent_skill_damaged_by_lv: number | null;
    prevent_skill_damaged_ratio: number | null;
    prevent_skill_damaged_ratio_by_lv: number | null;
    penetration_defense: number | null;
    penetration_defense_ratio: number | null;
    trap_damage_reduce: number | null;
    trap_damage_reduce_ratio: number | null;
    slow_resist_ratio: number | null;
    hp_healed_increase_ratio: number | null;
    healer_give_hp_heal_ratio: number | null;
    unique_attack_range: number | null;
    unique_hp_healed_increase_ratio: number | null;
    unique_cooldown_limit: number | null;
    unique_tenacity: number | null;
    unique_move_speed: number | null;
    unique_penetration_defense: number | null;
    unique_penetration_defense_ratio: number | null;
    unique_life_steal: number | null;
    unique_skill_amp_ratio: number | null;
    created_at: string;
    updated_at: string;
    equipment_skills?: EquipmentSkill[];
}

export interface EquipmentSkill {
    id: number;
    name: string;
    grade: string | null;
    sub_category: string | null;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export interface VersionHistory {
    id: number;
    version_season: number | null;
    version_major: number;
    version_minor: number;
    start_date: string;
    end_date: string;
    created_at: string;
    updated_at: string;
    version?: string;
    status?: string;
    patch_notes?: PatchNote[];
}

export interface PatchNote {
    id: number;
    version_history_id: number;
    category: string;
    target_id: number | null;
    weapon_type: string | null;
    skill_type: string | null;
    patch_type: string;
    content: string;
    created_at: string;
    updated_at: string;
    target_name?: string | null;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface SelectOption {
    value: string | number;
    label: string;
}
