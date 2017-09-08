
-- tp_order_goods 新增列　goods_thumbnail 保存商品缩略图。　Hua 2017-9-6
ALTER TABLE `pinquduo`.`tp_order_goods` ADD COLUMN `goods_thumbnail` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '' COMMENT '商品缩略图' AFTER `goods_num`