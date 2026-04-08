DROP TABLE IF EXISTS urls;

CREATE TABLE urls (
    id bigint primary key generated always as identity,
    name varchar(255) not null,
    created_at timestamp
);