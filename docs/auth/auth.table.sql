-- Updated: create all tables first, then add foreign keys and indexes afterwards
BEGIN;

CREATE SCHEMA IF NOT EXISTS auth;

-- Drop tables if existing (to allow re-run). Constraints removed implicitly when dropping tables.
DROP TABLE IF EXISTS auth.group_user;
DROP TABLE IF EXISTS auth.groups;
DROP TABLE IF EXISTS auth.login_attempts;
DROP TABLE IF EXISTS auth.password_resets;
DROP TABLE IF EXISTS auth.role_user;
DROP TABLE IF EXISTS auth.roles;
DROP TABLE IF EXISTS auth.sessions;
DROP TABLE IF EXISTS auth.user_details;
DROP TABLE IF EXISTS auth.users;
DROP TABLE IF EXISTS auth.verification_codes;

-- Create tables (no foreign key constraints here)
CREATE TABLE IF NOT EXISTS auth.group_user
(
    id bigserial NOT NULL,
    user_id uuid NOT NULL,
    group_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT group_user_pkey PRIMARY KEY (id),
    CONSTRAINT group_user_user_id_group_id_unique UNIQUE (user_id, group_id)
);

CREATE TABLE IF NOT EXISTS auth.groups
(
    id bigserial NOT NULL,
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    display_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    description text COLLATE pg_catalog."default",
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT groups_pkey PRIMARY KEY (id),
    CONSTRAINT groups_name_unique UNIQUE (name)
);

CREATE TABLE IF NOT EXISTS auth.login_attempts
(
    id bigserial NOT NULL,
    ip_address inet,
    email character varying(255) COLLATE pg_catalog."default",
    attempts integer NOT NULL DEFAULT 1,
    last_attempt timestamp(0) without time zone DEFAULT now(),
    blocked_until timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT login_attempts_pkey PRIMARY KEY (id),
    CONSTRAINT login_attempts_ip_email_unique UNIQUE (ip_address, email)
);

CREATE TABLE IF NOT EXISTS auth.password_resets
(
    id bigserial NOT NULL,
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    token character varying(255) COLLATE pg_catalog."default" NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT password_resets_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS auth.role_user
(
    id bigserial NOT NULL,
    user_id uuid NOT NULL,
    role_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT role_user_pkey PRIMARY KEY (id),
    CONSTRAINT role_user_user_id_role_id_unique UNIQUE (user_id, role_id)
);

CREATE TABLE IF NOT EXISTS auth.roles
(
    id bigserial NOT NULL,
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    display_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    description text COLLATE pg_catalog."default",
    is_active boolean NOT NULL DEFAULT true,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT roles_pkey PRIMARY KEY (id),
    CONSTRAINT roles_name_unique UNIQUE (name)
);

CREATE TABLE IF NOT EXISTS auth.sessions
(
    id bigserial NOT NULL,
	session_id text NOT NULL,
    user_id uuid,
    ip_address character varying(45) COLLATE pg_catalog."default",
    user_agent text COLLATE pg_catalog."default",
    payload jsonb NOT NULL,
    last_activity integer NOT NULL,
    expires_at timestamp(0) without time zone,
    device_type character varying(255) COLLATE pg_catalog."default",
    device_name character varying(255) COLLATE pg_catalog."default",
    platform character varying(255) COLLATE pg_catalog."default",
    browser character varying(255) COLLATE pg_catalog."default",
    city character varying(255) COLLATE pg_catalog."default",
    country character varying(255) COLLATE pg_catalog."default",
    is_current boolean NOT NULL DEFAULT false,
    is_trusted boolean NOT NULL DEFAULT false,
    last_used_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT sessions_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS auth.user_details
(
    id bigserial NOT NULL,
    user_id uuid NOT NULL,
    first_name character varying(255) COLLATE pg_catalog."default",
    last_name character varying(255) COLLATE pg_catalog."default",
    phone character varying(255) COLLATE pg_catalog."default",
    date_of_birth date,
    gender character varying(255) COLLATE pg_catalog."default",
    unit_no text COLLATE pg_catalog."default",
    street_name text COLLATE pg_catalog."default",
    city character varying(255) COLLATE pg_catalog."default",
    state character varying(255) COLLATE pg_catalog."default",
    postcode character varying(255) COLLATE pg_catalog."default",
    country character varying(255) COLLATE pg_catalog."default",
    employee_id character varying(255) COLLATE pg_catalog."default",
    telegram_id integer,
    bio text COLLATE pg_catalog."default",
    profile_picture character varying(255) COLLATE pg_catalog."default",
    preferences json,
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT user_details_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS auth.users
(
    id uuid NOT NULL DEFAULT uuid_generate_v4(),
    name character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email character varying(255) COLLATE pg_catalog."default" NOT NULL,
    password character varying(255) COLLATE pg_catalog."default" NOT NULL,
    email_verified_at timestamp(0) without time zone,
    is_active boolean NOT NULL DEFAULT true,
    last_login_at timestamp(0) without time zone,
    remember_token character varying(255) COLLATE pg_catalog."default",
    created_at timestamp(0) without time zone DEFAULT now(),
    updated_at timestamp(0) without time zone DEFAULT now(),
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_email_unique UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS auth.verification_codes
(
    id bigserial NOT NULL,
    user_id uuid,
    code character varying(10) COLLATE pg_catalog."default",
    expires_at timestamp without time zone,
    created_at timestamp without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT verification_codes_pkey PRIMARY KEY (id),
    CONSTRAINT verification_codes_user_id_unique UNIQUE (user_id)
);

-- After all tables are created: add foreign key constraints and indexes
ALTER TABLE IF EXISTS auth.group_user
    ADD CONSTRAINT group_user_group_id_foreign FOREIGN KEY (group_id)
    REFERENCES auth.groups (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS group_user_group_id_index
    ON auth.group_user(group_id);

ALTER TABLE IF EXISTS auth.group_user
    ADD CONSTRAINT group_user_user_id_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS group_user_user_id_index
    ON auth.group_user(user_id);

ALTER TABLE IF EXISTS auth.role_user
    ADD CONSTRAINT role_user_role_id_foreign FOREIGN KEY (role_id)
    REFERENCES auth.roles (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS role_user_role_id_index
    ON auth.role_user(role_id);

ALTER TABLE IF EXISTS auth.role_user
    ADD CONSTRAINT role_user_user_id_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS role_user_user_id_index
    ON auth.role_user(user_id);

ALTER TABLE IF EXISTS auth.login_attempts
    ADD CONSTRAINT login_attempts_email_foreign FOREIGN KEY (email)
    REFERENCES auth.users (email) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS login_attempts_email_index
    ON auth.login_attempts(email);

ALTER TABLE IF EXISTS auth.password_resets
    ADD CONSTRAINT password_resets_email_foreign FOREIGN KEY (email)
    REFERENCES auth.users (email) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS password_resets_email_index
    ON auth.password_resets(email);

ALTER TABLE IF EXISTS auth.sessions
    ADD CONSTRAINT sessions_user_id_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS sessions_user_id_index
    ON auth.sessions(user_id);

ALTER TABLE IF EXISTS auth.user_details
    ADD CONSTRAINT user_details_user_id_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS user_details_user_id_index
    ON auth.user_details(user_id);

ALTER TABLE IF EXISTS auth.verification_codes
    ADD CONSTRAINT verification_codes_user_id_foreign FOREIGN KEY (user_id)
    REFERENCES auth.users (id) MATCH SIMPLE
    ON UPDATE NO ACTION
    ON DELETE CASCADE;
CREATE INDEX IF NOT EXISTS verification_codes_user_id_unique
    ON auth.verification_codes(user_id);

COMMIT;
