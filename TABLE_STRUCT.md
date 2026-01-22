-- auto-generated definition
create table game_results_equipment_main_summary
(
id                          bigint unsigned auto_increment
primary key,
equipment_id                int                      not null,
equipment_name              varchar(255)             not null,
meta_tier                   varchar(255)             null,
meta_score                  decimal(10, 3)           null,
min_tier                    varchar(255)             not null,
min_score                   int                      not null,
game_count                  int                      not null,
positive_game_count         int                      not null,
negative_game_count         int                      not null,
game_count_percent          decimal(10, 3)           not null,
positive_game_count_percent decimal(10, 3)           not null,
negative_game_count_percent decimal(10, 3)           not null,
top1_count                  int                      not null,
top2_count                  int                      not null,
top4_count                  int                      not null,
top1_count_percent          decimal(10, 3)           not null,
top2_count_percent          decimal(10, 3)           not null,
top4_count_percent          decimal(10, 3)           not null,
endgame_win_percent         decimal(10, 3)           null,
avg_mmr_gain                decimal(10, 3)           not null,
avg_team_kill_score         decimal(10, 3)           null,
positive_avg_mmr_gain       decimal(10, 3)           not null,
negative_avg_mmr_gain       decimal(10, 3)           not null,
version_major               smallint                 not null,
version_minor               smallint                 not null,
created_at                  timestamp                null,
updated_at                  timestamp                null,
version_season              varchar(255) default '1' null
);

create index idx_equip_main_meta_score
on game_results_equipment_main_summary (meta_score);

create index idx_equipment_main_composite
on game_results_equipment_main_summary (version_season, version_major, version_minor, min_tier, equipment_id);



-- auto-generated definition
create table game_results_equipment_summary
(
id                    bigint unsigned auto_increment
primary key,
equipment_id          int                      null comment '장비 id',
character_id          int                      null comment '캐릭터 id',
weapon_type           varchar(255)             null comment '무기타입',
game_rank             int                      null comment '순위',
game_rank_count       int                      null comment '게임 수',
positive_count        int                      null comment '이득 게임 수',
negative_count        int                      null comment '손실 게임 수',
avg_mmr_gain          decimal(10, 3)           null comment '평균 점수 획득',
avg_team_kill_score   decimal(10, 3)           null,
positive_avg_mmr_gain decimal(10, 3)           null comment '평균 이득 점수 획득',
negative_avg_mmr_gain decimal(10, 3)           null comment '평균 손실 점수 획득',
min_tier              varchar(255)             null,
min_score             int                      null,
version_major         int                      null,
version_minor         int                      null,
created_at            timestamp                null,
updated_at            timestamp                null,
version_season        varchar(255) default '1' null
);

create index idx_equipment_char_weapon
on game_results_equipment_summary (character_id, weapon_type(20));

create index idx_equipment_composite
on game_results_equipment_summary (version_season, version_major, version_minor, min_tier, character_id, weapon_type(
50));

create index idx_equipment_optimal
on game_results_equipment_summary (character_id, weapon_type, version_season, version_major, version_minor,
min_tier);

create index idx_equipment_rank_count
on game_results_equipment_summary (game_rank_count desc, game_rank asc);

create index idx_equipment_version_tier
on game_results_equipment_summary (version_season, version_major, version_minor, min_tier(20));



-- auto-generated definition
create table game_results_first_equipment_main_summary
(
id                          bigint unsigned auto_increment
primary key,
equipment_id                int                      not null,
equipment_name              varchar(255)             not null,
meta_tier                   varchar(255)             null,
meta_score                  decimal(10, 3)           null,
min_tier                    varchar(255)             not null,
min_score                   int                      not null,
game_count                  int                      not null,
positive_game_count         int                      not null,
negative_game_count         int                      not null,
game_count_percent          decimal(10, 3)           not null,
positive_game_count_percent decimal(10, 3)           not null,
negative_game_count_percent decimal(10, 3)           not null,
top1_count                  int                      not null,
top2_count                  int                      not null,
top4_count                  int                      not null,
top1_count_percent          decimal(10, 3)           not null,
top2_count_percent          decimal(10, 3)           not null,
top4_count_percent          decimal(10, 3)           not null,
endgame_win_percent         decimal(10, 3)           not null,
avg_mmr_gain                decimal(10, 3)           not null,
avg_team_kill_score         decimal(10, 3)           null,
positive_avg_mmr_gain       decimal(10, 3)           not null,
negative_avg_mmr_gain       decimal(10, 3)           not null,
version_major               smallint                 not null,
version_minor               smallint                 not null,
created_at                  timestamp                null,
updated_at                  timestamp                null,
version_season              varchar(255) default '1' null
);

create index idx_first_equip_meta_score
on game_results_first_equipment_main_summary (meta_score);

create index idx_first_equipment_composite
on game_results_first_equipment_main_summary (version_season, version_major, version_minor, min_tier, equipment_id);



-- auto-generated definition
create table game_results_rank_summary
(
id                    bigint unsigned auto_increment
primary key,
character_id          int                      null,
character_name        varchar(255)             null,
weapon_type           varchar(255)             null,
game_rank             int                      null,
game_rank_count       int                      null,
avg_mmr_gain          decimal(10, 3)           null,
avg_team_kill_score   decimal(10, 3)           null,
positive_count        int                      null,
negative_count        int                      null,
positive_avg_mmr_gain decimal(10, 3)           null,
negative_avg_mmr_gain decimal(10, 3)           null,
min_tier              varchar(255)             null,
min_score             int                      null,
version_major         int                      null,
version_minor         int                      null,
created_at            timestamp                null,
updated_at            timestamp                null,
version_season        varchar(255) default '1' null
);

create index idx_rank_char_weapon
on game_results_rank_summary (character_id, weapon_type(20));

create index idx_rank_composite
on game_results_rank_summary (version_season, version_major, version_minor, min_tier, character_id, weapon_type(50),
game_rank);

create index idx_rank_version_tier
on game_results_rank_summary (version_season, version_major, version_minor, min_tier(20));



-- auto-generated definition
create table game_results_summary
(
id                          bigint unsigned auto_increment
primary key,
character_id                int                      not null,
character_name              varchar(255)             not null,
weapon_type                 varchar(255)             not null,
meta_score                  decimal(10, 3)           null,
meta_tier                   varchar(255)             null,
min_tier                    varchar(255)             not null,
min_score                   int                      not null,
game_count                  int                      not null,
positive_game_count         int                      not null,
negative_game_count         int                      not null,
game_count_percent          decimal(10, 3)           not null,
positive_game_count_percent decimal(10, 3)           not null,
negative_game_count_percent decimal(10, 3)           not null,
top1_count                  int                      not null,
top2_count                  int                      not null,
top4_count                  int                      not null,
top1_count_percent          decimal(10, 3)           not null,
top2_count_percent          decimal(10, 3)           not null,
top4_count_percent          decimal(10, 3)           not null,
endgame_win_percent         decimal(10, 3)           null,
avg_mmr_gain                decimal(10, 3)           not null,
avg_team_kill_score         decimal(10, 3)           null,
positive_avg_mmr_gain       decimal(10, 3)           not null,
negative_avg_mmr_gain       decimal(10, 3)           not null,
version_major               smallint                 not null,
version_minor               smallint                 not null,
created_at                  timestamp                null,
updated_at                  timestamp                null,
version_season              varchar(255) default '1' null
);

create index idx_detail_query
on game_results_summary (character_id, weapon_type, version_season, version_major, version_minor, min_tier);

create index idx_meta_score
on game_results_summary (meta_score);

create index idx_summary_composite
on game_results_summary (version_season, version_major, version_minor, min_tier, character_id, weapon_type(50));

create index idx_summary_full_lookup
on game_results_summary (version_season, version_major, version_minor, min_tier, character_id, weapon_type);

create index idx_summary_version_tier_character
on game_results_summary (version_season, version_major, version_minor, min_tier, character_id);



-- auto-generated definition
create table game_results_tactical_skill_summary
(
id                    bigint unsigned auto_increment
primary key,
tactical_skill_id     int                      null comment '전술스킬 id',
tactical_skill_level  int                      null comment '전술스킬 레벨',
character_id          int                      null comment '캐릭터 id',
weapon_type           varchar(255)             null comment '무기타입',
game_rank             int                      null comment '순위',
game_rank_count       int                      null comment '게임 수',
positive_count        int                      null comment '이득 게임 수',
negative_count        int                      null comment '손실 게임 수',
avg_mmr_gain          decimal(10, 3)           null comment '평균 점수 획득',
avg_team_kill_score   decimal(10, 3)           null,
positive_avg_mmr_gain decimal(10, 3)           null comment '평균 이득 점수 획득',
negative_avg_mmr_gain decimal(10, 3)           null comment '평균 손실 점수 획득',
min_tier              varchar(255)             null,
min_score             int                      null,
version_major         int                      null,
version_minor         int                      null,
created_at            timestamp                null,
updated_at            timestamp                null,
version_season        varchar(255) default '1' null
);

create index idx_tactical_char_weapon
on game_results_tactical_skill_summary (character_id, weapon_type(20));

create index idx_tactical_composite
on game_results_tactical_skill_summary (version_season, version_major, version_minor, min_tier, character_id,
weapon_type(50));

create index idx_tactical_optimal
on game_results_tactical_skill_summary (character_id, weapon_type, version_season, version_major, version_minor,
min_tier);

create index idx_tactical_rank_count
on game_results_tactical_skill_summary (game_rank_count desc, game_rank asc);

create index idx_tactical_version_tier
on game_results_tactical_skill_summary (version_season, version_major, version_minor, min_tier(20));



-- auto-generated definition
create table game_results_trait_combination_summary
(
id                          bigint unsigned auto_increment
primary key,
character_id                int            not null,
character_name              varchar(255)   not null,
weapon_type                 varchar(255)   not null,
trait_ids                   varchar(255)   not null comment '정렬된 특성 ID 조합 (예: 101,205,308)',
min_tier                    varchar(255)   not null,
min_score                   int            not null,
game_count                  int            not null,
positive_game_count         int            not null,
negative_game_count         int            not null,
game_count_percent          decimal(10, 3) not null comment '해당 캐릭터 내 픽률',
positive_game_count_percent decimal(10, 3) not null,
negative_game_count_percent decimal(10, 3) not null,
top1_count                  int            not null,
top2_count                  int            not null,
top4_count                  int            not null,
top1_count_percent          decimal(10, 3) not null,
top2_count_percent          decimal(10, 3) not null,
top4_count_percent          decimal(10, 3) not null,
endgame_win_percent         decimal(10, 3) null,
avg_mmr_gain                decimal(10, 3) not null,
positive_avg_mmr_gain       decimal(10, 3) not null,
negative_avg_mmr_gain       decimal(10, 3) not null,
avg_team_kill_score         decimal(10, 3) null,
version_season              smallint       not null,
version_major               smallint       not null,
version_minor               smallint       not null,
created_at                  timestamp      null,
updated_at                  timestamp      null,
constraint trait_combination_summary_unique
unique (character_id, weapon_type, trait_ids, min_tier, version_season, version_major, version_minor)
);

create index trait_combination_char_weapon_idx
on game_results_trait_combination_summary (character_id, weapon_type);

create index trait_combination_version_tier_idx
on game_results_trait_combination_summary (version_season, version_major, version_minor, min_tier);



-- auto-generated definition
create table game_results_trait_main_summary
(
id                          bigint unsigned auto_increment
primary key,
trait_id                    int            not null comment '특성 id',
trait_name                  varchar(255)   not null comment '특성 이름',
is_main                     tinyint(1)     not null comment '메인 특성 여부',
meta_tier                   varchar(255)   null comment '메타 티어',
meta_score                  decimal(10, 3) null comment '메타 점수',
min_tier                    varchar(255)   not null comment '최소 티어',
min_score                   int            not null comment '최소 점수',
game_count                  int            not null comment '게임 수',
positive_game_count         int            not null comment '이득 게임 수',
negative_game_count         int            not null comment '손실 게임 수',
game_count_percent          decimal(10, 3) not null comment '픽률',
positive_game_count_percent decimal(10, 3) not null comment '이득 확률',
negative_game_count_percent decimal(10, 3) not null comment '손실 확률',
top1_count                  int            not null comment '1위 횟수',
top2_count                  int            not null comment 'TOP2 횟수',
top4_count                  int            not null comment 'TOP4 횟수',
top1_count_percent          decimal(10, 3) not null comment '승률',
top2_count_percent          decimal(10, 3) not null comment 'TOP2 비율',
top4_count_percent          decimal(10, 3) not null comment 'TOP4 비율',
endgame_win_percent         decimal(10, 3) null comment '막금구 승률',
avg_mmr_gain                decimal(10, 3) not null comment '평균 획득 점수',
avg_team_kill_score         decimal(10, 3) null comment '평균 TK',
positive_avg_mmr_gain       decimal(10, 3) not null comment '이득 시 평균 획득 점수',
negative_avg_mmr_gain       decimal(10, 3) not null comment '손실 시 평균 획득 점수',
version_season              smallint       null,
version_major               smallint       not null,
version_minor               smallint       not null,
created_at                  timestamp      null,
updated_at                  timestamp      null,
constraint game_results_trait_main_summary_unique
unique (trait_id, is_main, min_tier, version_season, version_major, version_minor)
);

create index trait_main_summary_version_idx
on game_results_trait_main_summary (min_tier, version_season, version_major, version_minor);



-- auto-generated definition
create table game_results_trait_summary
(
id                    bigint unsigned auto_increment
primary key,
trait_id              int                      null comment '특성 id',
is_main               tinyint(1)               null comment '메인특성여부',
character_id          int                      null comment '캐릭터 id',
weapon_type           varchar(255)             null comment '무기타입',
game_rank             int                      null comment '순위',
game_rank_count       int                      null comment '게임 수',
positive_count        int                      null comment '이득 게임 수',
negative_count        int                      null comment '손실 게임 수',
avg_mmr_gain          decimal(10, 3)           null comment '평균 점수 획득',
avg_team_kill_score   decimal(10, 3)           null,
positive_avg_mmr_gain decimal(10, 3)           null comment '평균 이득 점수 획득',
negative_avg_mmr_gain decimal(10, 3)           null comment '평균 손실 점수 획득',
min_tier              varchar(255)             null,
min_score             int                      null,
version_major         int                      null,
version_minor         int                      null,
created_at            timestamp                null,
updated_at            timestamp                null,
version_season        varchar(255) default '1' null
);

create index idx_trait_char_weapon
on game_results_trait_summary (character_id, weapon_type(20));

create index idx_trait_composite
on game_results_trait_summary (version_season, version_major, version_minor, min_tier, character_id, weapon_type(
50));

create index idx_trait_optimal
on game_results_trait_summary (character_id, weapon_type, version_season, version_major, version_minor, min_tier);

create index idx_trait_rank_count
on game_results_trait_summary (game_rank_count desc, game_rank asc);

create index idx_trait_version_tier
on game_results_trait_summary (version_season, version_major, version_minor, min_tier(20));

