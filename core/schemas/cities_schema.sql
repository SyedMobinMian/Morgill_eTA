-- ============================================================
--  cities_schema.sql — Cities lookup table
--  Run karo: phpMyAdmin → dcform_db → Import
--  ONLY run this AFTER db_schema.sql is already imported
-- ============================================================
USE dcform_db;

CREATE TABLE IF NOT EXISTS cities (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    state_id  SMALLINT UNSIGNED NOT NULL,
    name      VARCHAR(100) NOT NULL,
    INDEX idx_state (state_id),
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ══════════════════════════════════════════════
-- INDIA CITIES (major cities per state)
-- ══════════════════════════════════════════════

-- Helper: get state IDs dynamically
SET @ap   = (SELECT id FROM states WHERE name='Andhra Pradesh'    LIMIT 1);
SET @ar   = (SELECT id FROM states WHERE name='Arunachal Pradesh' LIMIT 1);
SET @as   = (SELECT id FROM states WHERE name='Assam'             LIMIT 1);
SET @br   = (SELECT id FROM states WHERE name='Bihar'             LIMIT 1);
SET @cg   = (SELECT id FROM states WHERE name='Chhattisgarh'      LIMIT 1);
SET @ga   = (SELECT id FROM states WHERE name='Goa'               LIMIT 1);
SET @gj   = (SELECT id FROM states WHERE name='Gujarat'           LIMIT 1);
SET @hr   = (SELECT id FROM states WHERE name='Haryana'           LIMIT 1);
SET @hp   = (SELECT id FROM states WHERE name='Himachal Pradesh'  LIMIT 1);
SET @jh   = (SELECT id FROM states WHERE name='Jharkhand'         LIMIT 1);
SET @ka   = (SELECT id FROM states WHERE name='Karnataka'         LIMIT 1);
SET @kl   = (SELECT id FROM states WHERE name='Kerala'            LIMIT 1);
SET @mp   = (SELECT id FROM states WHERE name='Madhya Pradesh'    LIMIT 1);
SET @mh   = (SELECT id FROM states WHERE name='Maharashtra'       LIMIT 1);
SET @mn   = (SELECT id FROM states WHERE name='Manipur'           LIMIT 1);
SET @ml   = (SELECT id FROM states WHERE name='Meghalaya'         LIMIT 1);
SET @mz   = (SELECT id FROM states WHERE name='Mizoram'           LIMIT 1);
SET @nl   = (SELECT id FROM states WHERE name='Nagaland'          LIMIT 1);
SET @od   = (SELECT id FROM states WHERE name='Odisha'            LIMIT 1);
SET @pb   = (SELECT id FROM states WHERE name='Punjab'            LIMIT 1);
SET @rj   = (SELECT id FROM states WHERE name='Rajasthan'         LIMIT 1);
SET @sk   = (SELECT id FROM states WHERE name='Sikkim'            LIMIT 1);
SET @tn   = (SELECT id FROM states WHERE name='Tamil Nadu'        LIMIT 1);
SET @tl   = (SELECT id FROM states WHERE name='Telangana'         LIMIT 1);
SET @tr   = (SELECT id FROM states WHERE name='Tripura'           LIMIT 1);
SET @up   = (SELECT id FROM states WHERE name='Uttar Pradesh'     LIMIT 1);
SET @uk   = (SELECT id FROM states WHERE name='Uttarakhand'       LIMIT 1);
SET @wb   = (SELECT id FROM states WHERE name='West Bengal'       LIMIT 1);
SET @dl   = (SELECT id FROM states WHERE name='Delhi'             LIMIT 1);
SET @jk   = (SELECT id FROM states WHERE name='Jammu & Kashmir'   LIMIT 1);
SET @la   = (SELECT id FROM states WHERE name='Ladakh'            LIMIT 1);

INSERT INTO cities (state_id, name) VALUES
-- Andhra Pradesh
(@ap,'Visakhapatnam'),(@ap,'Vijayawada'),(@ap,'Guntur'),(@ap,'Nellore'),(@ap,'Kurnool'),(@ap,'Tirupati'),(@ap,'Rajahmundry'),(@ap,'Kakinada'),(@ap,'Kadapa'),(@ap,'Anantapur'),
-- Assam
(@as,'Guwahati'),(@as,'Silchar'),(@as,'Dibrugarh'),(@as,'Jorhat'),(@as,'Nagaon'),(@as,'Tinsukia'),(@as,'Tezpur'),
-- Bihar
(@br,'Patna'),(@br,'Gaya'),(@br,'Bhagalpur'),(@br,'Muzaffarpur'),(@br,'Darbhanga'),(@br,'Purnia'),(@br,'Arrah'),(@br,'Begusarai'),
-- Chhattisgarh
(@cg,'Raipur'),(@cg,'Bhilai'),(@cg,'Bilaspur'),(@cg,'Korba'),(@cg,'Durg'),(@cg,'Rajnandgaon'),
-- Goa
(@ga,'Panaji'),(@ga,'Margao'),(@ga,'Vasco da Gama'),(@ga,'Mapusa'),(@ga,'Ponda'),
-- Gujarat
(@gj,'Ahmedabad'),(@gj,'Surat'),(@gj,'Vadodara'),(@gj,'Rajkot'),(@gj,'Bhavnagar'),(@gj,'Jamnagar'),(@gj,'Gandhinagar'),(@gj,'Junagadh'),(@gj,'Anand'),(@gj,'Navsari'),
-- Haryana
(@hr,'Faridabad'),(@hr,'Gurugram'),(@hr,'Panipat'),(@hr,'Ambala'),(@hr,'Yamunanagar'),(@hr,'Rohtak'),(@hr,'Hisar'),(@hr,'Karnal'),(@hr,'Sonipat'),(@hr,'Panchkula'),
-- Himachal Pradesh
(@hp,'Shimla'),(@hp,'Dharamshala'),(@hp,'Solan'),(@hp,'Mandi'),(@hp,'Kangra'),(@hp,'Kullu'),(@hp,'Manali'),
-- Jharkhand
(@jh,'Ranchi'),(@jh,'Jamshedpur'),(@jh,'Dhanbad'),(@jh,'Bokaro'),(@jh,'Deoghar'),(@jh,'Hazaribagh'),
-- Karnataka
(@ka,'Bengaluru'),(@ka,'Mysuru'),(@ka,'Mangaluru'),(@ka,'Hubballi'),(@ka,'Dharwad'),(@ka,'Belagavi'),(@ka,'Davangere'),(@ka,'Ballari'),(@ka,'Tumkur'),(@ka,'Shivamogga'),
-- Kerala
(@kl,'Thiruvananthapuram'),(@kl,'Kochi'),(@kl,'Kozhikode'),(@kl,'Thrissur'),(@kl,'Kollam'),(@kl,'Kannur'),(@kl,'Alappuzha'),(@kl,'Palakkad'),(@kl,'Malappuram'),
-- Madhya Pradesh
(@mp,'Bhopal'),(@mp,'Indore'),(@mp,'Gwalior'),(@mp,'Jabalpur'),(@mp,'Ujjain'),(@mp,'Sagar'),(@mp,'Rewa'),(@mp,'Satna'),(@mp,'Dewas'),(@mp,'Ratlam'),
-- Maharashtra
(@mh,'Mumbai'),(@mh,'Pune'),(@mh,'Nagpur'),(@mh,'Thane'),(@mh,'Nashik'),(@mh,'Aurangabad'),(@mh,'Solapur'),(@mh,'Navi Mumbai'),(@mh,'Amravati'),(@mh,'Kolhapur'),(@mh,'Dhule'),(@mh,'Latur'),(@mh,'Ahmednagar'),
-- Odisha
(@od,'Bhubaneswar'),(@od,'Cuttack'),(@od,'Rourkela'),(@od,'Berhampur'),(@od,'Sambalpur'),(@od,'Puri'),
-- Punjab
(@pb,'Ludhiana'),(@pb,'Amritsar'),(@pb,'Jalandhar'),(@pb,'Patiala'),(@pb,'Bathinda'),(@pb,'Mohali'),(@pb,'Pathankot'),(@pb,'Hoshiarpur'),(@pb,'Gurdaspur'),
-- Rajasthan
(@rj,'Jaipur'),(@rj,'Jodhpur'),(@rj,'Kota'),(@rj,'Bikaner'),(@rj,'Ajmer'),(@rj,'Udaipur'),(@rj,'Bhilwara'),(@rj,'Alwar'),(@rj,'Bharatpur'),(@rj,'Sikar'),
-- Tamil Nadu
(@tn,'Chennai'),(@tn,'Coimbatore'),(@tn,'Madurai'),(@tn,'Tiruchirappalli'),(@tn,'Salem'),(@tn,'Tirunelveli'),(@tn,'Vellore'),(@tn,'Erode'),(@tn,'Thoothukkudi'),(@tn,'Dindigul'),
-- Telangana
(@tl,'Hyderabad'),(@tl,'Warangal'),(@tl,'Nizamabad'),(@tl,'Karimnagar'),(@tl,'Khammam'),(@tl,'Mahbubnagar'),(@tl,'Ramagundam'),
-- Uttar Pradesh
(@up,'Lucknow'),(@up,'Kanpur'),(@up,'Agra'),(@up,'Varanasi'),(@up,'Prayagraj'),(@up,'Ghaziabad'),(@up,'Noida'),(@up,'Meerut'),(@up,'Bareilly'),(@up,'Aligarh'),(@up,'Moradabad'),(@up,'Saharanpur'),(@up,'Gorakhpur'),(@up,'Mathura'),(@up,'Firozabad'),(@up,'Rampur'),(@up,'Shahjahanpur'),(@up,'Muzaffarnagar'),(@up,'Jhansi'),(@up,'Amroha'),
-- Uttarakhand
(@uk,'Dehradun'),(@uk,'Haridwar'),(@uk,'Roorkee'),(@uk,'Rishikesh'),(@uk,'Nainital'),(@uk,'Haldwani'),(@uk,'Rudrapur'),
-- West Bengal
(@wb,'Kolkata'),(@wb,'Howrah'),(@wb,'Durgapur'),(@wb,'Asansol'),(@wb,'Siliguri'),(@wb,'Maheshtala'),(@wb,'Rajpur Sonarpur'),(@wb,'South Dumdum'),(@wb,'Bardhaman'),(@wb,'Malda'),
-- Delhi
(@dl,'New Delhi'),(@dl,'Dwarka'),(@dl,'Rohini'),(@dl,'Janakpuri'),(@dl,'Karol Bagh'),(@dl,'Lajpat Nagar'),(@dl,'Connaught Place'),(@dl,'Saket'),(@dl,'Pitampura'),(@dl,'Shahdara'),
-- Jammu & Kashmir
(@jk,'Srinagar'),(@jk,'Jammu'),(@jk,'Anantnag'),(@jk,'Baramulla'),(@jk,'Sopore'),
-- Ladakh
(@la,'Leh'),(@la,'Kargil');

-- ══════════════════════════════════════════════
-- USA CITIES (major cities per state)
-- ══════════════════════════════════════════════
SET @ca_us = (SELECT id FROM states WHERE name='California'  LIMIT 1);
SET @ny_us = (SELECT id FROM states WHERE name='New York'    LIMIT 1);
SET @tx_us = (SELECT id FROM states WHERE name='Texas'       LIMIT 1);
SET @fl_us = (SELECT id FROM states WHERE name='Florida'     LIMIT 1);
SET @il_us = (SELECT id FROM states WHERE name='Illinois'    LIMIT 1);
SET @wa_us = (SELECT id FROM states WHERE name='Washington'  LIMIT 1);

INSERT INTO cities (state_id, name) VALUES
(@ca_us,'Los Angeles'),(@ca_us,'San Francisco'),(@ca_us,'San Diego'),(@ca_us,'San Jose'),(@ca_us,'Sacramento'),(@ca_us,'Fresno'),(@ca_us,'Long Beach'),(@ca_us,'Oakland'),
(@ny_us,'New York City'),(@ny_us,'Buffalo'),(@ny_us,'Rochester'),(@ny_us,'Yonkers'),(@ny_us,'Syracuse'),(@ny_us,'Albany'),
(@tx_us,'Houston'),(@tx_us,'San Antonio'),(@tx_us,'Dallas'),(@tx_us,'Austin'),(@tx_us,'Fort Worth'),(@tx_us,'El Paso'),(@tx_us,'Arlington'),(@tx_us,'Plano'),
(@fl_us,'Jacksonville'),(@fl_us,'Miami'),(@fl_us,'Tampa'),(@fl_us,'Orlando'),(@fl_us,'St. Petersburg'),(@fl_us,'Hialeah'),
(@il_us,'Chicago'),(@il_us,'Aurora'),(@il_us,'Rockford'),(@il_us,'Joliet'),(@il_us,'Naperville'),
(@wa_us,'Seattle'),(@wa_us,'Spokane'),(@wa_us,'Tacoma'),(@wa_us,'Bellevue'),(@wa_us,'Kent');

-- ══════════════════════════════════════════════
-- CANADA CITIES
-- ══════════════════════════════════════════════
SET @on = (SELECT id FROM states WHERE name='Ontario'          LIMIT 1);
SET @bc = (SELECT id FROM states WHERE name='British Columbia' LIMIT 1);
SET @ab = (SELECT id FROM states WHERE name='Alberta'          LIMIT 1);
SET @qc = (SELECT id FROM states WHERE name='Quebec'           LIMIT 1);

INSERT INTO cities (state_id, name) VALUES
(@on,'Toronto'),(@on,'Ottawa'),(@on,'Mississauga'),(@on,'Brampton'),(@on,'Hamilton'),(@on,'London'),(@on,'Markham'),(@on,'Vaughan'),
(@bc,'Vancouver'),(@bc,'Surrey'),(@bc,'Burnaby'),(@bc,'Richmond'),(@bc,'Kelowna'),(@bc,'Abbotsford'),
(@ab,'Calgary'),(@ab,'Edmonton'),(@ab,'Red Deer'),(@ab,'Lethbridge'),(@ab,'St. Albert'),
(@qc,'Montreal'),(@qc,'Quebec City'),(@qc,'Laval'),(@qc,'Gatineau'),(@qc,'Longueuil'),(@qc,'Sherbrooke');

-- ══════════════════════════════════════════════
-- UK CITIES
-- ══════════════════════════════════════════════
SET @eng = (SELECT id FROM states WHERE name='England'          LIMIT 1);
SET @sco = (SELECT id FROM states WHERE name='Scotland'         LIMIT 1);
SET @wal = (SELECT id FROM states WHERE name='Wales'            LIMIT 1);
SET @ni  = (SELECT id FROM states WHERE name='Northern Ireland' LIMIT 1);

INSERT INTO cities (state_id, name) VALUES
(@eng,'London'),(@eng,'Birmingham'),(@eng,'Manchester'),(@eng,'Leeds'),(@eng,'Sheffield'),(@eng,'Bristol'),(@eng,'Liverpool'),(@eng,'Newcastle'),(@eng,'Nottingham'),(@eng,'Leicester'),
(@sco,'Edinburgh'),(@sco,'Glasgow'),(@sco,'Aberdeen'),(@sco,'Dundee'),(@sco,'Inverness'),
(@wal,'Cardiff'),(@wal,'Swansea'),(@wal,'Newport'),(@wal,'Wrexham'),
(@ni,'Belfast'),(@ni,'Derry'),(@ni,'Lisburn'),(@ni,'Newry');

-- ══════════════════════════════════════════════
-- AUSTRALIA CITIES
-- ══════════════════════════════════════════════
SET @nsw = (SELECT id FROM states WHERE name='New South Wales' LIMIT 1);
SET @vic = (SELECT id FROM states WHERE name='Victoria'        LIMIT 1);
SET @qld = (SELECT id FROM states WHERE name='Queensland'      LIMIT 1);
SET @wa_au= (SELECT id FROM states WHERE name='Western Australia' LIMIT 1);

INSERT INTO cities (state_id, name) VALUES
(@nsw,'Sydney'),(@nsw,'Newcastle'),(@nsw,'Wollongong'),(@nsw,'Coffs Harbour'),(@nsw,'Albury'),
(@vic,'Melbourne'),(@vic,'Geelong'),(@vic,'Ballarat'),(@vic,'Bendigo'),(@vic,'Shepparton'),
(@qld,'Brisbane'),(@qld,'Gold Coast'),(@qld,'Cairns'),(@qld,'Townsville'),(@qld,'Sunshine Coast'),
(@wa_au,'Perth'),(@wa_au,'Fremantle'),(@wa_au,'Bunbury'),(@wa_au,'Geraldton');
