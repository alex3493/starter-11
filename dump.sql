use laravel;

create table cache
(
    `key`      varchar(255) not null
        primary key,
    value      mediumtext   not null,
    expiration int          not null
)
    collate = utf8mb4_unicode_ci;

create table cache_locks
(
    `key`      varchar(255) not null
        primary key,
    owner      varchar(255) not null,
    expiration int          not null
)
    collate = utf8mb4_unicode_ci;

create table chats
(
    id         bigint unsigned auto_increment
        primary key,
    topic      varchar(255) not null,
    created_at timestamp    null,
    updated_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table failed_jobs
(
    id         bigint unsigned auto_increment
        primary key,
    uuid       varchar(255)                        not null,
    connection text                                not null,
    queue      text                                not null,
    payload    longtext                            not null,
    exception  longtext                            not null,
    failed_at  timestamp default CURRENT_TIMESTAMP not null,
    constraint failed_jobs_uuid_unique
        unique (uuid)
)
    collate = utf8mb4_unicode_ci;

create table job_batches
(
    id             varchar(255) not null
        primary key,
    name           varchar(255) not null,
    total_jobs     int          not null,
    pending_jobs   int          not null,
    failed_jobs    int          not null,
    failed_job_ids longtext     not null,
    options        mediumtext   null,
    cancelled_at   int          null,
    created_at     int          not null,
    finished_at    int          null
)
    collate = utf8mb4_unicode_ci;

create table jobs
(
    id           bigint unsigned auto_increment
        primary key,
    queue        varchar(255)     not null,
    payload      longtext         not null,
    attempts     tinyint unsigned not null,
    reserved_at  int unsigned     null,
    available_at int unsigned     not null,
    created_at   int unsigned     not null
)
    collate = utf8mb4_unicode_ci;

create index jobs_queue_index
    on jobs (queue);

create table migrations
(
    id        int unsigned auto_increment
        primary key,
    migration varchar(255) not null,
    batch     int          not null
)
    collate = utf8mb4_unicode_ci;

create table password_reset_tokens
(
    email      varchar(255) not null
        primary key,
    token      varchar(255) not null,
    created_at timestamp    null
)
    collate = utf8mb4_unicode_ci;

create table personal_access_tokens
(
    id             bigint unsigned auto_increment
        primary key,
    tokenable_type varchar(255)    not null,
    tokenable_id   bigint unsigned not null,
    name           varchar(255)    not null,
    token          varchar(64)     not null,
    abilities      text            null,
    last_used_at   timestamp       null,
    expires_at     timestamp       null,
    created_at     timestamp       null,
    updated_at     timestamp       null,
    constraint personal_access_tokens_token_unique
        unique (token)
)
    collate = utf8mb4_unicode_ci;

create index personal_access_tokens_tokenable_type_tokenable_id_index
    on personal_access_tokens (tokenable_type, tokenable_id);

create table pulse_aggregates
(
    id        bigint unsigned auto_increment
        primary key,
    bucket    int unsigned   not null,
    period mediumint unsigned not null,
    type      varchar(255)   not null,
    `key`     mediumtext     not null,
    key_hash  binary(16) as (unhex(md5(`key`))),
    aggregate varchar(255)   not null,
    value     decimal(20, 2) not null,
    count     int unsigned   null,
    constraint pulse_aggregates_bucket_period_type_aggregate_key_hash_unique
        unique (bucket, period, type, aggregate, key_hash)
)
    collate = utf8mb4_unicode_ci;

create index pulse_aggregates_period_bucket_index
    on pulse_aggregates (period, bucket);

create index pulse_aggregates_period_type_aggregate_bucket_index
    on pulse_aggregates (period, type, aggregate, bucket);

create index pulse_aggregates_type_index
    on pulse_aggregates (type);

create table pulse_entries
(
    id        bigint unsigned auto_increment
        primary key,
    timestamp int unsigned not null,
    type      varchar(255) not null,
    `key`     mediumtext   not null,
    key_hash  binary(16) as (unhex(md5(`key`))),
    value     bigint       null
)
    collate = utf8mb4_unicode_ci;

create index pulse_entries_key_hash_index
    on pulse_entries (key_hash);

create index pulse_entries_timestamp_index
    on pulse_entries (timestamp);

create index pulse_entries_timestamp_type_key_hash_value_index
    on pulse_entries (timestamp, type, key_hash, value);

create index pulse_entries_type_index
    on pulse_entries (type);

create table pulse_values
(
    id        bigint unsigned auto_increment
        primary key,
    timestamp int unsigned not null,
    type      varchar(255) not null,
    `key`     mediumtext   not null,
    key_hash  binary(16) as (unhex(md5(`key`))),
    value     mediumtext   not null,
    constraint pulse_values_type_key_hash_unique
        unique (type, key_hash)
)
    collate = utf8mb4_unicode_ci;

create index pulse_values_timestamp_index
    on pulse_values (timestamp);

create index pulse_values_type_index
    on pulse_values (type);

create table sessions
(
    id            varchar(255)    not null
        primary key,
    user_id       bigint unsigned null,
    ip_address    varchar(45)     null,
    user_agent    text            null,
    payload       longtext        not null,
    last_activity int             not null
)
    collate = utf8mb4_unicode_ci;

create index sessions_last_activity_index
    on sessions (last_activity);

create index sessions_user_id_index
    on sessions (user_id);

create table users
(
    id                bigint unsigned auto_increment
        primary key,
    name              varchar(255) not null,
    email             varchar(255) not null,
    email_verified_at timestamp    null,
    password          varchar(255) not null,
    remember_token    varchar(100) null,
    created_at        timestamp    null,
    updated_at        timestamp    null,
    constraint users_email_unique
        unique (email)
)
    collate = utf8mb4_unicode_ci;

create table chat_messages
(
    id         bigint unsigned auto_increment
        primary key,
    user_id    bigint unsigned not null,
    chat_id    bigint unsigned not null,
    message    text            not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint chat_messages_chat_id_foreign
        foreign key (chat_id) references chats (id)
            on delete cascade,
    constraint chat_messages_user_id_foreign
        foreign key (user_id) references users (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

create table chat_user
(
    id         bigint unsigned auto_increment
        primary key,
    chat_id    bigint unsigned not null,
    user_id    bigint unsigned not null,
    created_at timestamp       null,
    updated_at timestamp       null,
    constraint chat_user_chat_id_foreign
        foreign key (chat_id) references chats (id)
            on delete cascade,
    constraint chat_user_user_id_foreign
        foreign key (user_id) references users (id)
            on delete cascade
)
    collate = utf8mb4_unicode_ci;

insert into users
values ("1", "Admin", "admin@starter.loc", null, "$2y$12$oV/7ZXpqo/IG0X3mHVW.6OHVw3HIVL.QJJxCwSk9ASk1FwSKk95XO", null,
        "2024-04-08 15:35:18", "2024-04-08 15:35:18");

insert into migrations
values (1, "0001_01_01_000000_create_users_table", 1),
       (2, "0001_01_01_000001_create_cache_table", 1),
       (3, "0001_01_01_000002_create_jobs_table", 1),
       (4, "2024_04_04_065417_create_pulse_tables", 1),
       (5, "2024_04_04_071913_create_personal_access_tokens_table", 1),
       (6, "2024_04_05_074025_create_chats_table", 1),
       (7, "2024_04_05_074054_create_chats_messages_table", 1),
       (8, "2024_04_05_074104_create_chat_user_table", 1);
