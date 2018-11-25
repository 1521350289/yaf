<?php
/**
 * Created by PhpStorm.
 * User: Hasee
 * Date: 2018/11/23
 * Time: 下午 9:21
 */

class ArtModel
{
    public $errno = 0;
    public $errmsg = "";
    private $_db = null;

    public function __construct()
    {
        if (!$this->_db){
            $this->_db = new PDO("mysql:host=127.0.0.1;dbname=yaf;","homestead",'secret',array(PDO::ATTR_PERSISTENT=>true));
            $this->_db->query("set names utf8");
        }
        //解决pdo 拼sql时 会把int 0 转成 string 0
        $this->_db->setAttribute(PDO::ATTR_EMULATE_PREPARES,false);
    }

    public function add($title,$contents,$author,$cate,$artId=0)
    {
        $isEdit = false;
        if ($artId!=0 && is_numeric($artId)){
            //edit
            $query = $this->_db->prepare("select count(*) from `art` where `id`=?");
            $query->execute(array($artId));
            $ret = $query->fetchAll();
            if (!$ret || count($ret) != 1){
                $this->errno = -2004;
                $this->errmsg = "找不到你要编辑的文章";
                return false;
            }
            $isEdit = true;
        }else{
            //add
            $query = $this->_db->prepare("select count(*) from `cate` where `id` = ?");
            $query->execute(array($cate));
            $ret = $query->fetchAll();
            if (!$ret || $ret[0][0] == 0){
                $this->errno = -2005;
                $this->errmsg = "找不到对应的ID的分类信息,cate id:".$cate."，请先创建该分类。";
                return false;
            }
        }

        //插入或更新文章内容
        $data = array($title,$contents,$author,intval($cate));
        if (!$isEdit){
            $query = $this->_db->prepare("insert into `art` (`title`,`contents`,`author`,`cate`) values (?,?,?,?)");
        }else{
            $query = $this->_db->prepare("update `art` set `title` = ?,`contents`=?,`author`=?,`cate`=? where `id`=?");
            $data[]=$artId;
        }
        $ret = $query->execute($data);
        $query->debugDumpParams();
        if (!$ret){
            $this->errno = -2006;
            $this->errmsg = "操作文章数据表失败,ErrInfo:".end($query->errorInfo());
            return false;
        }

        //返回文章最后的id
        if (!$isEdit){
            return intval($this->_db->lastInsertId());
        }else{
            return intval($artId);
        }
    }

    public function del($artId)
    {
        $query = $this->_db->prepare("delete from `art` where `id`=?");
        $ret = $query->execute(array(intval($artId)));
        if (!$ret){
            $this->errno = -2007;
            $this->errmsg = "删除失败,ErrInfo:".end($query->errorInfo());
            return false;
        }
        return true;
    }

    public function status($artId,$status="offline")
    {
        $query = $this->_db->prepare("update `art` set `status`=? where `id`=?");
        $ret = $query->execute(array($status,intval($artId)));
        if (!$ret){
            $this->errno = -2008;
            $this->errmsg = "更新文章状态失败,ErrInfo:".end($query->errorInfo());
            return false;
        }
        return true;
    }

    public function get($artId)
    {
        $query = $this->_db->prepare("select `title`,`contents`,`author`,`cate`,`ctime`,`mtime`,`status` from `art` where `id`=?");
        $status = $query->execute(array(intval($artId)));
        $ret = $query->fetchAll();
        if (!$status || !$ret){
            $this->errno = -2009;
            $this->errmsg = "查询失败，ErrInfo:".end($query->errorInfo());
            return false;
        }
        $artInfo = $ret[0];
        //获取分类信息
        $query = $this->_db->prepare("select `name` from `cate` where `id`=?");
        $query->execute(array($artInfo['cate']));
        $ret = $query->fetchAll();
        if (!$ret){
            $this->errno = -2010;
            $this->errmsg = "获取分类信息失败,ErrInfo:".end($query->errorInfo());
            return false;
        }
        $artInfo['cateName'] = $ret[0]['name'];

        $data = array(
            'id'=>intval($artId),
            'title' => $artInfo['title'],
            'contents' => $artInfo['contents'],
            'author' => $artInfo['author'],
            'cateName'=>$artInfo['cateName'],
            'cateId'=>intval($artInfo['cate']),
            'ctime' => $artInfo['ctime'],
            'mtime' => $artInfo['mtime'],
            'status'=>$artInfo['status']
        );
        return $data;
    }

}