create table reports (
    id       int unsigned not null primary key auto_increment,
    nid      int unsigned not null,
    path     varchar(255) not null,
    created  timestamp    not null default CURRENT_TIMESTAMP,
    error    tinyint unsigned,
    contrast tinyint unsigned,
    alert    tinyint unsigned,
    report   json
);

create table departments (
    id       int unsigned not null primary key auto_increment,
    name     varchar(32)  not null unique,
    title    varchar(64),
    dn       varchar(255) not null unique,
    nid      int unsigned
);

create table users (
    id         int unsigned not null primary key,
    username   varchar(32)  not null unique,
    department varchar(32),
    foreign key (department) references departments(name)
);

create table analytics (
    path  varchar(255) not null primary key,
    views int unsigned not null
);

create table grackle_results (
    path     varchar(255)     not null,
    filename varchar(255)     not null,
    url      varchar(255)     not null,
    score    tinyint unsigned not null,
    scanned  datetime         not null
);

insert into departments(nid, name, title, dn) values
(16  , 'Clerk'         , 'City Clerk'                           , 'OU=City Clerk,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(18  , 'CFRD'          , 'Community and Family Resources'       , 'OU=Community and Family Resources,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(19  , 'Controller'    , 'Controller'                           , 'OU=Controller,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(2796, 'Council'       , 'Common Council'                       , 'OU=Council Office,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(3   , 'ESD'           , 'Economic and Sustainable Development' , 'OU=Economic & Sustainable Development,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(4712, 'Engineering'   , 'Engineering'                          , 'OU=Engineering,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(22  , 'HAND'          , 'Housing and Neighborhood Development' , 'OU=HAND,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(20  , 'HR'            , 'Human Resources'                      , 'OU=Human Resources,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(1   , 'ITS'           , 'Information & Technology Services'    , 'OU=ITS,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(23  , 'Legal'         , 'Legal'                                , 'OU=Legal,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(14  , 'OOTM'          , 'Office Of The Mayor'                  , 'OU=Office of the Mayor,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(7   , 'Parks'         , 'Parks & Recreation Department'        , 'OU=Parks and Recreation,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(24  , 'Planning'      , 'Planning and Transportation'          , 'OU=Planning and Transportation,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(NULL, 'Animal Shelter', NULL                                   ,     'OU=Animal Shelter,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(NULL, 'Facilities'    , NULL                                   ,         'OU=Facilities,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(NULL, 'Fleet'         , NULL                                   ,  'OU=Fleet Maintenance,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(737 , 'Parking'       , 'Parking Services'                     ,   'OU=Parking Services,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(NULL, 'Sanitation'    , NULL                                   ,         'OU=Sanitation,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(738 , 'Street'        , 'Street Division'                      , 'OU=Street and Traffic,OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(26  , 'Public Works'  , 'Public Works'                         ,                       'OU=Public Works,OU=City Hall,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(21  , 'Fire'          , 'Fire Department'                      , 'OU=Fire,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(25  , 'Police'        , 'Police Department'                    , 'OU=Police,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov'),
(27  , 'Utilities'     , 'Utilities'                            , 'OU=Utilities,OU=Departments,DC=cob,DC=bloomington,DC=in,DC=gov')
;
