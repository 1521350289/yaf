<?php
/**
 * Created by PhpStorm.
 * User: Hasee
 * Date: 2018/11/25
 * Time: 下午 5:28
 */
require __DIR__.'/../../vendor/autoload.php';
use Nette\Mail\Message;
class MailModel
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

    public function send($uid,$title,$contents)
    {
        $query = $this->_db->prepare("select `email` from `user` where `id`=?");
        $query->execute(array(intval($uid)));
        $ret = $query->fetchAll();
        if (!$ret || count($ret)!=1){
            $this->errno = -3003;
            $this->errmsg = "用户邮箱信息查找失败";
            return false;
        }
        $userEmail = $ret[0]['email'];
        if (!filter_var($userEmail,FILTER_VALIDATE_EMAIL)){
            $this->errno = -3004;
            $this->errmsg = "用户邮箱信息不符合标准,邮箱地址为:".$userEmail;
            return false;
        }

        $mail = new Message();
        $mail->setFrom('come from hitagi ')
            ->addTo($userEmail)
            ->setSubject($title)
            ->setBody($contents);

        $mailer = new \Nette\Mail\SmtpMailer([
            'host' => 'smtp.126.com',
            'username' => '',
            'password'=>'',
            'secure'=>'ssl'
        ]);
        $rep = $mailer->send($mail);
        return true;
    }
}