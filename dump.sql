--
-- PostgreSQL database dump
--

-- Dumped from database version 12.9 (Ubuntu 12.9-0ubuntu0.20.04.1)
-- Dumped by pg_dump version 12.9 (Ubuntu 12.9-0ubuntu0.20.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: item; Type: TABLE; Schema: public; Owner: price
--

CREATE TABLE public.item (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    article character varying(32) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created time with time zone DEFAULT now() NOT NULL,
    _updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.item OWNER TO price;

--
-- Name: TABLE item; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON TABLE public.item IS 'Позиции';


--
-- Name: COLUMN item.name; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.item.name IS 'Название позиции';


--
-- Name: COLUMN item.article; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.item.article IS 'Артикул';


--
-- Name: item_id_seq; Type: SEQUENCE; Schema: public; Owner: price
--

CREATE SEQUENCE public.item_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.item_id_seq OWNER TO price;

--
-- Name: item_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: price
--

ALTER SEQUENCE public.item_id_seq OWNED BY public.item.id;


--
-- Name: price_item_log; Type: TABLE; Schema: public; Owner: price
--

CREATE TABLE public.price_item_log (
    id integer NOT NULL,
    id_price_item integer NOT NULL,
    old_price numeric(15,2),
    new_price numeric(15,2) NOT NULL,
    old_active boolean,
    new_active boolean NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.price_item_log OWNER TO price;

--
-- Name: TABLE price_item_log; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON TABLE public.price_item_log IS 'Журнал изменений';


--
-- Name: price_irtem_log_id_seq; Type: SEQUENCE; Schema: public; Owner: price
--

CREATE SEQUENCE public.price_irtem_log_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.price_irtem_log_id_seq OWNER TO price;

--
-- Name: price_irtem_log_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: price
--

ALTER SEQUENCE public.price_irtem_log_id_seq OWNED BY public.price_item_log.id;


--
-- Name: price_item; Type: TABLE; Schema: public; Owner: price
--

CREATE TABLE public.price_item (
    id integer NOT NULL,
    id_price_list integer NOT NULL,
    id_item integer NOT NULL,
    price numeric(15,2) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    _updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.price_item OWNER TO price;

--
-- Name: TABLE price_item; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON TABLE public.price_item IS 'Позиции прайс листа';


--
-- Name: COLUMN price_item.id_price_list; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_item.id_price_list IS 'Праис лист';


--
-- Name: COLUMN price_item.id_item; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_item.id_item IS 'Позиция';


--
-- Name: COLUMN price_item.price; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_item.price IS 'Стоимость';


--
-- Name: price_item_id_seq; Type: SEQUENCE; Schema: public; Owner: price
--

CREATE SEQUENCE public.price_item_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.price_item_id_seq OWNER TO price;

--
-- Name: price_item_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: price
--

ALTER SEQUENCE public.price_item_id_seq OWNED BY public.price_item.id;


--
-- Name: price_list; Type: TABLE; Schema: public; Owner: price
--

CREATE TABLE public.price_list (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    id_provider integer NOT NULL,
    date date NOT NULL,
    currency character varying(3) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    _updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.price_list OWNER TO price;

--
-- Name: TABLE price_list; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON TABLE public.price_list IS 'Прайс листы';


--
-- Name: COLUMN price_list.id_provider; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_list.id_provider IS 'Поставщик';


--
-- Name: COLUMN price_list.date; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_list.date IS 'Дата прайс листа';


--
-- Name: COLUMN price_list.currency; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.price_list.currency IS 'Валюта';


--
-- Name: price_list_id_seq; Type: SEQUENCE; Schema: public; Owner: price
--

CREATE SEQUENCE public.price_list_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.price_list_id_seq OWNER TO price;

--
-- Name: price_list_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: price
--

ALTER SEQUENCE public.price_list_id_seq OWNED BY public.price_list.id;


--
-- Name: provider; Type: TABLE; Schema: public; Owner: price
--

CREATE TABLE public.provider (
    id integer NOT NULL,
    name character varying(255) NOT NULL,
    active boolean DEFAULT true NOT NULL,
    created timestamp with time zone DEFAULT now() NOT NULL,
    _updated timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.provider OWNER TO price;

--
-- Name: COLUMN provider.name; Type: COMMENT; Schema: public; Owner: price
--

COMMENT ON COLUMN public.provider.name IS 'Название';


--
-- Name: provider_id_seq; Type: SEQUENCE; Schema: public; Owner: price
--

CREATE SEQUENCE public.provider_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.provider_id_seq OWNER TO price;

--
-- Name: provider_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: price
--

ALTER SEQUENCE public.provider_id_seq OWNED BY public.provider.id;


--
-- Name: item id; Type: DEFAULT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.item ALTER COLUMN id SET DEFAULT nextval('public.item_id_seq'::regclass);


--
-- Name: price_item id; Type: DEFAULT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item ALTER COLUMN id SET DEFAULT nextval('public.price_item_id_seq'::regclass);


--
-- Name: price_item_log id; Type: DEFAULT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item_log ALTER COLUMN id SET DEFAULT nextval('public.price_irtem_log_id_seq'::regclass);


--
-- Name: price_list id; Type: DEFAULT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_list ALTER COLUMN id SET DEFAULT nextval('public.price_list_id_seq'::regclass);


--
-- Name: provider id; Type: DEFAULT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.provider ALTER COLUMN id SET DEFAULT nextval('public.provider_id_seq'::regclass);


--
-- Data for Name: item; Type: TABLE DATA; Schema: public; Owner: price
--

INSERT INTO public.item VALUES (19, 'Хлеб белый', '11', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (20, 'Хлеб Чёрный', '12', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (21, 'Молоко 1%', 'm10', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (22, 'Молоко 3.2%', 'm32', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (23, 'Молоко 6%', 'm60', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (24, 'Кефир 1%', '1000', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (25, 'Рубашка', '54685681', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (26, 'Пиджак', '345785638', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (27, 'Брюки', '2457245', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (28, 'Футболка', '2457dfsh', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (29, 'Телевизор', '22214545', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (30, 'Смартфон', '43213334', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (31, 'Ноутбук', '3566721', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (32, 'Монитор', '346346', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (33, 'Холодильник', '457457', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (34, 'Чайник', '346346', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');
INSERT INTO public.item VALUES (35, 'Кружка', '3463146', true, '20:12:17.946753+03', '2022-02-02 20:12:17.946753+03');


--
-- Data for Name: price_item; Type: TABLE DATA; Schema: public; Owner: price
--

INSERT INTO public.price_item VALUES (6, 40, 29, 499.00, true, '2022-02-02 23:04:33.233032+03', '2022-02-02 23:04:33.233032+03');
INSERT INTO public.price_item VALUES (9, 40, 30, 399.00, true, '2022-02-02 23:17:40.880008+03', '2022-02-02 23:17:40.880008+03');
INSERT INTO public.price_item VALUES (5, 40, 31, 999.99, true, '2022-02-02 23:02:51.907531+03', '2022-02-02 23:02:51.907531+03');


--
-- Data for Name: price_item_log; Type: TABLE DATA; Schema: public; Owner: price
--

INSERT INTO public.price_item_log VALUES (3, 9, NULL, 9.00, NULL, true, '2022-02-02 23:17:40.880008+03');
INSERT INTO public.price_item_log VALUES (5, 9, 399.00, 398.00, true, true, '2022-02-02 23:24:29.514177+03');
INSERT INTO public.price_item_log VALUES (6, 9, 399.00, 398.00, true, true, '2022-02-02 23:24:59.699312+03');
INSERT INTO public.price_item_log VALUES (7, 9, 399.00, 398.00, true, true, '2022-02-02 23:39:51.632854+03');
INSERT INTO public.price_item_log VALUES (8, 9, 399.00, 398.00, true, true, '2022-02-02 23:41:26.542658+03');
INSERT INTO public.price_item_log VALUES (9, 9, 398.00, 399.00, true, true, '2022-02-02 23:41:38.381041+03');
INSERT INTO public.price_item_log VALUES (10, 5, 1010.00, 999.99, true, true, '2022-02-02 23:55:15.077182+03');
INSERT INTO public.price_item_log VALUES (11, 5, 1000.00, 999.90, true, true, '2022-02-02 23:57:25.121447+03');
INSERT INTO public.price_item_log VALUES (12, 5, 1000.00, 999.00, true, true, '2022-02-02 23:58:00.968289+03');
INSERT INTO public.price_item_log VALUES (13, 5, 999.00, 999.00, true, true, '2022-02-02 23:58:35.096605+03');
INSERT INTO public.price_item_log VALUES (14, 5, 999.00, 999.90, true, true, '2022-02-02 23:58:51.53401+03');
INSERT INTO public.price_item_log VALUES (15, 5, 1000.00, 999.00, true, true, '2022-02-02 23:59:26.849352+03');
INSERT INTO public.price_item_log VALUES (16, 5, 999.00, 999.99, true, true, '2022-02-03 00:03:26.753086+03');


--
-- Data for Name: price_list; Type: TABLE DATA; Schema: public; Owner: price
--

INSERT INTO public.price_list VALUES (37, 'Общий', 1, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (38, 'Продукты', 1, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (39, 'Товары', 1, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (40, 'Импорт', 1, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (41, 'Общий', 2, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (42, 'Импорт', 2, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (43, 'Одежда', 3, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (44, 'Техника', 3, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (45, 'Импорт', 3, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (46, 'Мясо', 4, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (47, 'Молоко', 4, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (48, 'Хлеб', 4, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (49, 'Кофе', 4, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (50, 'Основной', 5, '2022-02-02', 'RUB', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');
INSERT INTO public.price_list VALUES (51, 'Прочее', 5, '2022-02-02', 'USD', true, '2022-02-02 13:59:06.292087+03', '2022-02-02 13:59:06.292087+03');


--
-- Data for Name: provider; Type: TABLE DATA; Schema: public; Owner: price
--

INSERT INTO public.provider VALUES (1, 'Поставщик 1', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (2, 'Поставщик 2', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (3, 'Поставщик 3', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (4, 'Поставщик 4', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (5, 'Поставщик 5', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (6, 'Поставщик 6', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (7, 'Поставщик 7', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (8, 'Поставщик 8', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (9, 'Поставщик 9', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');
INSERT INTO public.provider VALUES (10, 'Поставщик 10', true, '2022-02-02 13:27:07.211503+03', '2022-02-02 13:27:07.211503+03');


--
-- Name: item_id_seq; Type: SEQUENCE SET; Schema: public; Owner: price
--

SELECT pg_catalog.setval('public.item_id_seq', 35, true);


--
-- Name: price_irtem_log_id_seq; Type: SEQUENCE SET; Schema: public; Owner: price
--

SELECT pg_catalog.setval('public.price_irtem_log_id_seq', 16, true);


--
-- Name: price_item_id_seq; Type: SEQUENCE SET; Schema: public; Owner: price
--

SELECT pg_catalog.setval('public.price_item_id_seq', 9, true);


--
-- Name: price_list_id_seq; Type: SEQUENCE SET; Schema: public; Owner: price
--

SELECT pg_catalog.setval('public.price_list_id_seq', 51, true);


--
-- Name: provider_id_seq; Type: SEQUENCE SET; Schema: public; Owner: price
--

SELECT pg_catalog.setval('public.provider_id_seq', 10, true);


--
-- Name: item item_pkey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.item
    ADD CONSTRAINT item_pkey PRIMARY KEY (id);


--
-- Name: price_item_log price_irtem_log_pkey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item_log
    ADD CONSTRAINT price_irtem_log_pkey PRIMARY KEY (id);


--
-- Name: price_item price_item_pkey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item
    ADD CONSTRAINT price_item_pkey PRIMARY KEY (id);


--
-- Name: price_item price_item_ukey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item
    ADD CONSTRAINT price_item_ukey UNIQUE (id_price_list, id_item);


--
-- Name: price_list price_list_pkey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_list
    ADD CONSTRAINT price_list_pkey PRIMARY KEY (id);


--
-- Name: price_list price_list_ukey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_list
    ADD CONSTRAINT price_list_ukey UNIQUE (name, id_provider, date);


--
-- Name: provider provider_name_ukey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.provider
    ADD CONSTRAINT provider_name_ukey UNIQUE (name);


--
-- Name: provider provider_pkey; Type: CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.provider
    ADD CONSTRAINT provider_pkey PRIMARY KEY (id);


--
-- Name: id_price_item_key; Type: INDEX; Schema: public; Owner: price
--

CREATE INDEX id_price_item_key ON public.price_item_log USING btree (id_price_item);


--
-- Name: item_article_key; Type: INDEX; Schema: public; Owner: price
--

CREATE INDEX item_article_key ON public.item USING btree (article);


--
-- Name: item_name_key; Type: INDEX; Schema: public; Owner: price
--

CREATE INDEX item_name_key ON public.item USING btree (name);


--
-- Name: price_item_log_created; Type: INDEX; Schema: public; Owner: price
--

CREATE INDEX price_item_log_created ON public.price_item_log USING btree (created);


--
-- Name: price_list_id_provider; Type: INDEX; Schema: public; Owner: price
--

CREATE INDEX price_list_id_provider ON public.price_list USING btree (id_provider);


--
-- Name: price_item_log price_irtem_log_fkey; Type: FK CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item_log
    ADD CONSTRAINT price_irtem_log_fkey FOREIGN KEY (id_price_item) REFERENCES public.price_item(id);


--
-- Name: price_item price_item_fkey; Type: FK CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item
    ADD CONSTRAINT price_item_fkey FOREIGN KEY (id_item) REFERENCES public.item(id);


--
-- Name: price_list price_list_fkey; Type: FK CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_list
    ADD CONSTRAINT price_list_fkey FOREIGN KEY (id_provider) REFERENCES public.provider(id);


--
-- Name: price_item price_list_fkey; Type: FK CONSTRAINT; Schema: public; Owner: price
--

ALTER TABLE ONLY public.price_item
    ADD CONSTRAINT price_list_fkey FOREIGN KEY (id_price_list) REFERENCES public.price_list(id);


--
-- PostgreSQL database dump complete
--

