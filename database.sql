DROP TABLE IF EXISTS url_checks;
DROP TABLE IF EXISTS urls;

CREATE TABLE urls (
    id bigint primary key generated always as identity,
    name varchar(255) unique not null,
    created_at timestamp default current_timestamp not null
);

CREATE TABLE url_checks (
    id bigint primary key generated always as identity,
    url_id bigint references urls(id) not null,
    status_code int not null,
    h1 varchar(1000),
    title text,
    description text,
    created_at timestamp default current_timestamp not null
);