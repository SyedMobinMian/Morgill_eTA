-- ============================================================
--  china_cities_seed.sql
--  Adds major cities for China provinces/regions.
--  Prerequisite: countries/states tables populated (CN + 31 states)
-- ============================================================
USE dcform_db;

SET @cn = (SELECT id FROM countries WHERE code='CN' LIMIT 1);

SET @anhui = (SELECT id FROM states WHERE country_id=@cn AND name='Anhui' LIMIT 1);
SET @beijing = (SELECT id FROM states WHERE country_id=@cn AND name='Beijing' LIMIT 1);
SET @chongqing = (SELECT id FROM states WHERE country_id=@cn AND name='Chongqing' LIMIT 1);
SET @fujian = (SELECT id FROM states WHERE country_id=@cn AND name='Fujian' LIMIT 1);
SET @gansu = (SELECT id FROM states WHERE country_id=@cn AND name='Gansu' LIMIT 1);
SET @guangdong = (SELECT id FROM states WHERE country_id=@cn AND name='Guangdong' LIMIT 1);
SET @guangxi = (SELECT id FROM states WHERE country_id=@cn AND name='Guangxi' LIMIT 1);
SET @guizhou = (SELECT id FROM states WHERE country_id=@cn AND name='Guizhou' LIMIT 1);
SET @hainan = (SELECT id FROM states WHERE country_id=@cn AND name='Hainan' LIMIT 1);
SET @hebei = (SELECT id FROM states WHERE country_id=@cn AND name='Hebei' LIMIT 1);
SET @heilongjiang = (SELECT id FROM states WHERE country_id=@cn AND name='Heilongjiang' LIMIT 1);
SET @henan = (SELECT id FROM states WHERE country_id=@cn AND name='Henan' LIMIT 1);
SET @hubei = (SELECT id FROM states WHERE country_id=@cn AND name='Hubei' LIMIT 1);
SET @hunan = (SELECT id FROM states WHERE country_id=@cn AND name='Hunan' LIMIT 1);
SET @inner_mongolia = (SELECT id FROM states WHERE country_id=@cn AND name='Inner Mongolia' LIMIT 1);
SET @jiangsu = (SELECT id FROM states WHERE country_id=@cn AND name='Jiangsu' LIMIT 1);
SET @jiangxi = (SELECT id FROM states WHERE country_id=@cn AND name='Jiangxi' LIMIT 1);
SET @jilin = (SELECT id FROM states WHERE country_id=@cn AND name='Jilin' LIMIT 1);
SET @liaoning = (SELECT id FROM states WHERE country_id=@cn AND name='Liaoning' LIMIT 1);
SET @ningxia = (SELECT id FROM states WHERE country_id=@cn AND name='Ningxia' LIMIT 1);
SET @qinghai = (SELECT id FROM states WHERE country_id=@cn AND name='Qinghai' LIMIT 1);
SET @shaanxi = (SELECT id FROM states WHERE country_id=@cn AND name='Shaanxi' LIMIT 1);
SET @shandong = (SELECT id FROM states WHERE country_id=@cn AND name='Shandong' LIMIT 1);
SET @shanghai = (SELECT id FROM states WHERE country_id=@cn AND name='Shanghai' LIMIT 1);
SET @shanxi = (SELECT id FROM states WHERE country_id=@cn AND name='Shanxi' LIMIT 1);
SET @sichuan = (SELECT id FROM states WHERE country_id=@cn AND name='Sichuan' LIMIT 1);
SET @tianjin = (SELECT id FROM states WHERE country_id=@cn AND name='Tianjin' LIMIT 1);
SET @tibet = (SELECT id FROM states WHERE country_id=@cn AND name='Tibet' LIMIT 1);
SET @xinjiang = (SELECT id FROM states WHERE country_id=@cn AND name='Xinjiang' LIMIT 1);
SET @yunnan = (SELECT id FROM states WHERE country_id=@cn AND name='Yunnan' LIMIT 1);
SET @zhejiang = (SELECT id FROM states WHERE country_id=@cn AND name='Zhejiang' LIMIT 1);

INSERT INTO cities (state_id, name)
SELECT @anhui, 'Hefei' FROM DUAL WHERE @anhui IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@anhui AND name='Hefei');
INSERT INTO cities (state_id, name)
SELECT @anhui, 'Wuhu' FROM DUAL WHERE @anhui IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@anhui AND name='Wuhu');
INSERT INTO cities (state_id, name)
SELECT @anhui, 'Bengbu' FROM DUAL WHERE @anhui IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@anhui AND name='Bengbu');

INSERT INTO cities (state_id, name)
SELECT @beijing, 'Beijing' FROM DUAL WHERE @beijing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@beijing AND name='Beijing');
INSERT INTO cities (state_id, name)
SELECT @beijing, 'Changping' FROM DUAL WHERE @beijing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@beijing AND name='Changping');
INSERT INTO cities (state_id, name)
SELECT @beijing, 'Haidian' FROM DUAL WHERE @beijing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@beijing AND name='Haidian');

INSERT INTO cities (state_id, name)
SELECT @chongqing, 'Chongqing' FROM DUAL WHERE @chongqing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@chongqing AND name='Chongqing');
INSERT INTO cities (state_id, name)
SELECT @chongqing, 'Wanzhou' FROM DUAL WHERE @chongqing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@chongqing AND name='Wanzhou');
INSERT INTO cities (state_id, name)
SELECT @chongqing, 'Fuling' FROM DUAL WHERE @chongqing IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@chongqing AND name='Fuling');

INSERT INTO cities (state_id, name)
SELECT @fujian, 'Fuzhou' FROM DUAL WHERE @fujian IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@fujian AND name='Fuzhou');
INSERT INTO cities (state_id, name)
SELECT @fujian, 'Xiamen' FROM DUAL WHERE @fujian IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@fujian AND name='Xiamen');
INSERT INTO cities (state_id, name)
SELECT @fujian, 'Quanzhou' FROM DUAL WHERE @fujian IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@fujian AND name='Quanzhou');

INSERT INTO cities (state_id, name)
SELECT @gansu, 'Lanzhou' FROM DUAL WHERE @gansu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@gansu AND name='Lanzhou');
INSERT INTO cities (state_id, name)
SELECT @gansu, 'Tianshui' FROM DUAL WHERE @gansu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@gansu AND name='Tianshui');
INSERT INTO cities (state_id, name)
SELECT @gansu, 'Jiuquan' FROM DUAL WHERE @gansu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@gansu AND name='Jiuquan');

INSERT INTO cities (state_id, name)
SELECT @guangdong, 'Guangzhou' FROM DUAL WHERE @guangdong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangdong AND name='Guangzhou');
INSERT INTO cities (state_id, name)
SELECT @guangdong, 'Shenzhen' FROM DUAL WHERE @guangdong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangdong AND name='Shenzhen');
INSERT INTO cities (state_id, name)
SELECT @guangdong, 'Dongguan' FROM DUAL WHERE @guangdong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangdong AND name='Dongguan');

INSERT INTO cities (state_id, name)
SELECT @guangxi, 'Nanning' FROM DUAL WHERE @guangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangxi AND name='Nanning');
INSERT INTO cities (state_id, name)
SELECT @guangxi, 'Guilin' FROM DUAL WHERE @guangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangxi AND name='Guilin');
INSERT INTO cities (state_id, name)
SELECT @guangxi, 'Liuzhou' FROM DUAL WHERE @guangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guangxi AND name='Liuzhou');

INSERT INTO cities (state_id, name)
SELECT @guizhou, 'Guiyang' FROM DUAL WHERE @guizhou IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guizhou AND name='Guiyang');
INSERT INTO cities (state_id, name)
SELECT @guizhou, 'Zunyi' FROM DUAL WHERE @guizhou IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guizhou AND name='Zunyi');
INSERT INTO cities (state_id, name)
SELECT @guizhou, 'Anshun' FROM DUAL WHERE @guizhou IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@guizhou AND name='Anshun');

INSERT INTO cities (state_id, name)
SELECT @hainan, 'Haikou' FROM DUAL WHERE @hainan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hainan AND name='Haikou');
INSERT INTO cities (state_id, name)
SELECT @hainan, 'Sanya' FROM DUAL WHERE @hainan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hainan AND name='Sanya');
INSERT INTO cities (state_id, name)
SELECT @hainan, 'Danzhou' FROM DUAL WHERE @hainan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hainan AND name='Danzhou');

INSERT INTO cities (state_id, name)
SELECT @hebei, 'Shijiazhuang' FROM DUAL WHERE @hebei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hebei AND name='Shijiazhuang');
INSERT INTO cities (state_id, name)
SELECT @hebei, 'Tangshan' FROM DUAL WHERE @hebei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hebei AND name='Tangshan');
INSERT INTO cities (state_id, name)
SELECT @hebei, 'Baoding' FROM DUAL WHERE @hebei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hebei AND name='Baoding');

INSERT INTO cities (state_id, name)
SELECT @heilongjiang, 'Harbin' FROM DUAL WHERE @heilongjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@heilongjiang AND name='Harbin');
INSERT INTO cities (state_id, name)
SELECT @heilongjiang, 'Qiqihar' FROM DUAL WHERE @heilongjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@heilongjiang AND name='Qiqihar');
INSERT INTO cities (state_id, name)
SELECT @heilongjiang, 'Mudanjiang' FROM DUAL WHERE @heilongjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@heilongjiang AND name='Mudanjiang');

INSERT INTO cities (state_id, name)
SELECT @henan, 'Zhengzhou' FROM DUAL WHERE @henan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@henan AND name='Zhengzhou');
INSERT INTO cities (state_id, name)
SELECT @henan, 'Luoyang' FROM DUAL WHERE @henan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@henan AND name='Luoyang');
INSERT INTO cities (state_id, name)
SELECT @henan, 'Nanyang' FROM DUAL WHERE @henan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@henan AND name='Nanyang');

INSERT INTO cities (state_id, name)
SELECT @hubei, 'Wuhan' FROM DUAL WHERE @hubei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hubei AND name='Wuhan');
INSERT INTO cities (state_id, name)
SELECT @hubei, 'Yichang' FROM DUAL WHERE @hubei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hubei AND name='Yichang');
INSERT INTO cities (state_id, name)
SELECT @hubei, 'Xiangyang' FROM DUAL WHERE @hubei IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hubei AND name='Xiangyang');

INSERT INTO cities (state_id, name)
SELECT @hunan, 'Changsha' FROM DUAL WHERE @hunan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hunan AND name='Changsha');
INSERT INTO cities (state_id, name)
SELECT @hunan, 'Zhuzhou' FROM DUAL WHERE @hunan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hunan AND name='Zhuzhou');
INSERT INTO cities (state_id, name)
SELECT @hunan, 'Xiangtan' FROM DUAL WHERE @hunan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@hunan AND name='Xiangtan');

INSERT INTO cities (state_id, name)
SELECT @inner_mongolia, 'Hohhot' FROM DUAL WHERE @inner_mongolia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@inner_mongolia AND name='Hohhot');
INSERT INTO cities (state_id, name)
SELECT @inner_mongolia, 'Baotou' FROM DUAL WHERE @inner_mongolia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@inner_mongolia AND name='Baotou');
INSERT INTO cities (state_id, name)
SELECT @inner_mongolia, 'Ordos' FROM DUAL WHERE @inner_mongolia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@inner_mongolia AND name='Ordos');

INSERT INTO cities (state_id, name)
SELECT @jiangsu, 'Nanjing' FROM DUAL WHERE @jiangsu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangsu AND name='Nanjing');
INSERT INTO cities (state_id, name)
SELECT @jiangsu, 'Suzhou' FROM DUAL WHERE @jiangsu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangsu AND name='Suzhou');
INSERT INTO cities (state_id, name)
SELECT @jiangsu, 'Wuxi' FROM DUAL WHERE @jiangsu IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangsu AND name='Wuxi');

INSERT INTO cities (state_id, name)
SELECT @jiangxi, 'Nanchang' FROM DUAL WHERE @jiangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangxi AND name='Nanchang');
INSERT INTO cities (state_id, name)
SELECT @jiangxi, 'Jiujiang' FROM DUAL WHERE @jiangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangxi AND name='Jiujiang');
INSERT INTO cities (state_id, name)
SELECT @jiangxi, 'Ganzhou' FROM DUAL WHERE @jiangxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jiangxi AND name='Ganzhou');

INSERT INTO cities (state_id, name)
SELECT @jilin, 'Changchun' FROM DUAL WHERE @jilin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jilin AND name='Changchun');
INSERT INTO cities (state_id, name)
SELECT @jilin, 'Jilin City' FROM DUAL WHERE @jilin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jilin AND name='Jilin City');
INSERT INTO cities (state_id, name)
SELECT @jilin, 'Siping' FROM DUAL WHERE @jilin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@jilin AND name='Siping');

INSERT INTO cities (state_id, name)
SELECT @liaoning, 'Shenyang' FROM DUAL WHERE @liaoning IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@liaoning AND name='Shenyang');
INSERT INTO cities (state_id, name)
SELECT @liaoning, 'Dalian' FROM DUAL WHERE @liaoning IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@liaoning AND name='Dalian');
INSERT INTO cities (state_id, name)
SELECT @liaoning, 'Anshan' FROM DUAL WHERE @liaoning IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@liaoning AND name='Anshan');

INSERT INTO cities (state_id, name)
SELECT @ningxia, 'Yinchuan' FROM DUAL WHERE @ningxia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@ningxia AND name='Yinchuan');
INSERT INTO cities (state_id, name)
SELECT @ningxia, 'Shizuishan' FROM DUAL WHERE @ningxia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@ningxia AND name='Shizuishan');
INSERT INTO cities (state_id, name)
SELECT @ningxia, 'Wuzhong' FROM DUAL WHERE @ningxia IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@ningxia AND name='Wuzhong');

INSERT INTO cities (state_id, name)
SELECT @qinghai, 'Xining' FROM DUAL WHERE @qinghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@qinghai AND name='Xining');
INSERT INTO cities (state_id, name)
SELECT @qinghai, 'Golmud' FROM DUAL WHERE @qinghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@qinghai AND name='Golmud');
INSERT INTO cities (state_id, name)
SELECT @qinghai, 'Delingha' FROM DUAL WHERE @qinghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@qinghai AND name='Delingha');

INSERT INTO cities (state_id, name)
SELECT @shaanxi, 'Xi''an' FROM DUAL WHERE @shaanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shaanxi AND name='Xi''an');
INSERT INTO cities (state_id, name)
SELECT @shaanxi, 'Xianyang' FROM DUAL WHERE @shaanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shaanxi AND name='Xianyang');
INSERT INTO cities (state_id, name)
SELECT @shaanxi, 'Baoji' FROM DUAL WHERE @shaanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shaanxi AND name='Baoji');

INSERT INTO cities (state_id, name)
SELECT @shandong, 'Jinan' FROM DUAL WHERE @shandong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shandong AND name='Jinan');
INSERT INTO cities (state_id, name)
SELECT @shandong, 'Qingdao' FROM DUAL WHERE @shandong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shandong AND name='Qingdao');
INSERT INTO cities (state_id, name)
SELECT @shandong, 'Yantai' FROM DUAL WHERE @shandong IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shandong AND name='Yantai');

INSERT INTO cities (state_id, name)
SELECT @shanghai, 'Shanghai' FROM DUAL WHERE @shanghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanghai AND name='Shanghai');
INSERT INTO cities (state_id, name)
SELECT @shanghai, 'Pudong' FROM DUAL WHERE @shanghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanghai AND name='Pudong');
INSERT INTO cities (state_id, name)
SELECT @shanghai, 'Minhang' FROM DUAL WHERE @shanghai IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanghai AND name='Minhang');

INSERT INTO cities (state_id, name)
SELECT @shanxi, 'Taiyuan' FROM DUAL WHERE @shanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanxi AND name='Taiyuan');
INSERT INTO cities (state_id, name)
SELECT @shanxi, 'Datong' FROM DUAL WHERE @shanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanxi AND name='Datong');
INSERT INTO cities (state_id, name)
SELECT @shanxi, 'Changzhi' FROM DUAL WHERE @shanxi IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@shanxi AND name='Changzhi');

INSERT INTO cities (state_id, name)
SELECT @sichuan, 'Chengdu' FROM DUAL WHERE @sichuan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@sichuan AND name='Chengdu');
INSERT INTO cities (state_id, name)
SELECT @sichuan, 'Mianyang' FROM DUAL WHERE @sichuan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@sichuan AND name='Mianyang');
INSERT INTO cities (state_id, name)
SELECT @sichuan, 'Deyang' FROM DUAL WHERE @sichuan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@sichuan AND name='Deyang');

INSERT INTO cities (state_id, name)
SELECT @tianjin, 'Tianjin' FROM DUAL WHERE @tianjin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tianjin AND name='Tianjin');
INSERT INTO cities (state_id, name)
SELECT @tianjin, 'Binhai' FROM DUAL WHERE @tianjin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tianjin AND name='Binhai');
INSERT INTO cities (state_id, name)
SELECT @tianjin, 'Wuqing' FROM DUAL WHERE @tianjin IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tianjin AND name='Wuqing');

INSERT INTO cities (state_id, name)
SELECT @tibet, 'Lhasa' FROM DUAL WHERE @tibet IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tibet AND name='Lhasa');
INSERT INTO cities (state_id, name)
SELECT @tibet, 'Shigatse' FROM DUAL WHERE @tibet IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tibet AND name='Shigatse');
INSERT INTO cities (state_id, name)
SELECT @tibet, 'Nyingchi' FROM DUAL WHERE @tibet IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@tibet AND name='Nyingchi');

INSERT INTO cities (state_id, name)
SELECT @xinjiang, 'Urumqi' FROM DUAL WHERE @xinjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@xinjiang AND name='Urumqi');
INSERT INTO cities (state_id, name)
SELECT @xinjiang, 'Kashgar' FROM DUAL WHERE @xinjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@xinjiang AND name='Kashgar');
INSERT INTO cities (state_id, name)
SELECT @xinjiang, 'Karamay' FROM DUAL WHERE @xinjiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@xinjiang AND name='Karamay');

INSERT INTO cities (state_id, name)
SELECT @yunnan, 'Kunming' FROM DUAL WHERE @yunnan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@yunnan AND name='Kunming');
INSERT INTO cities (state_id, name)
SELECT @yunnan, 'Dali' FROM DUAL WHERE @yunnan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@yunnan AND name='Dali');
INSERT INTO cities (state_id, name)
SELECT @yunnan, 'Qujing' FROM DUAL WHERE @yunnan IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@yunnan AND name='Qujing');

INSERT INTO cities (state_id, name)
SELECT @zhejiang, 'Hangzhou' FROM DUAL WHERE @zhejiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@zhejiang AND name='Hangzhou');
INSERT INTO cities (state_id, name)
SELECT @zhejiang, 'Ningbo' FROM DUAL WHERE @zhejiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@zhejiang AND name='Ningbo');
INSERT INTO cities (state_id, name)
SELECT @zhejiang, 'Wenzhou' FROM DUAL WHERE @zhejiang IS NOT NULL AND NOT EXISTS (SELECT 1 FROM cities WHERE state_id=@zhejiang AND name='Wenzhou');
