DROP TABLE IF EXISTS urls;
DROP TABLE IF EXISTS url_checks;

CREATE TABLE urls (
    id bigint primary key generated always as identity,
    name varchar(255) unique not null,
    created_at timestamp default current_timestamp not null
);

CREATE TABLE url_checks (
    id bigint primary key generated always as identity,
    url_id bigint references urls(id) not null,
    status_code int not null,
    h1 varchar(255),
    title varchar(255),
    description varchar(255),
    created_at timestamp default current_timestamp not null
);