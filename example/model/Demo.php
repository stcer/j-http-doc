<?php

namespace j\httpDoc\example\model {

/**
 * Class Access
 * @package module\interactive
 *
 * @property int id
 * @property string corpid, 管理的企业ID
 * @property string corp_app_id, 企业应用ID
 * @property int uid, 用户ID
 * @property int corp_admin_user_id, 访问人的企业管理员ID
 * @property string path, 访问路径
 * @property string info_type, 信息类型
 * @property int info_id, 信息ID
 * @property int share_uid, 分享人ID
 * @property int share_corp_admin_user_id, 分享人的企业管理员ID
 * @property int c_year, 年
 * @property int c_month, 月
 * @property int c_week, 周
 * @property int c_day, 日
 * @property string cdate, 创建时间
 */
class Access
{

}


/**
 * Class Message
 * @package module\interactive
 *
 * @property int id
 * @property string corpid, 管理的企业ID
 * @property string corp_app_id, 企业应用ID
 * @property int uid, 用户ID
 * @property string info_type, 信息类型
 * @property int info_id, 信息ID
 * @property string content, 留言内容
 * @property string clue_id, 线索ID
 * @property string linkman, 联系人
 * @property string phone, 手机号
 * @property string cdate, [创建时间]
 * @property int c_year, [年]
 * @property int c_month, [月]
 * @property int c_week, [周]
 * @property int c_day, [日]
 */
class Message{

}


/**
 * Class Product
 * @package module\product
 *
 * @property int id, ID
 * @property string corpid, 管理的企业ID
 * @property string corp_app_id, 企业应用ID
 * @property string title, 产品标题
 * @property float price_origin, 原价
 * @property float price, 价格
 * @property string image, 封面图
 * @property string images, 图集
 * @property string content, 内容
 * @property int stock_total, 总库存
 * @property int stock, 剩余库存
 * @property int enable, 上下架：1上架，2下架,3草稿箱
 * @property int inx, 排序ID
 * @property int cid, 产品分类
 * @property int hits, 点击数
 * @property string spec, 规格
 * @property int rmd, 是否推荐：1是，2否
 * @property string tags, 标签
 * @property string cdate, 创建时间
 */
class Product {

}
}
