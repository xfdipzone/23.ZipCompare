<?php
/** Zip Compare class 比较两个zip文件的内容，返回新增，删除，及相同的文件列表，暂时只支持单层。
*   Date:   2014-05-18
*   Author: fdipzone
*   Ver:    1.0
*
*   Func:
*   public  compare       比较zip文件内容
*   private getInfo       获取zip内文件列表
*   private parse         分析两个zip的文件内容
*   private check         检查zip文件是否正确
*   private check_handler 检查服务器是否有安装unzip
*/

class ZipCompare{ // class start

    /** 比较zip文件内容，列出不相同的部分
    * @param  String  $zipfile1 zip文件1
    * @param  String  $zipfile2 zip文件2
    * @return Array
    */
    public function compare($zipfile1, $zipfile2){

        // 检查是否有安装unzip
        if(!$this->check_handler()){
            throw new Exception('unzip not install');
        }

        // 检查zip文件
        if(!$this->check($zipfile1) || !$this->check($zipfile2)){
            throw new Exception('zipfile not exists or error');
        }

        // 获取zip内文件列表
        $zipinfo1 = $this->getInfo($zipfile1);
        $zipinfo2 = $this->getInfo($zipfile2);

        // 分析两个zip的文件内容，返回相同及不同的文件列表
        return $this->parse($zipinfo1, $zipinfo2);

    }


    /** 获取zip内文件列表
    * @param  String $zipfile zip文件
    * @return Array           zip内文件列表
    */
    private function getInfo($zipfile){

        // unzip -v fields
        $fields = array('Length','Method','Size','Cmpr','Date','Time','CRC-32','Name');

        // zip verbose
        $verbose = shell_exec(sprintf("unzip -v %s | sed '\$d' | sed '\$d' | sed -n '4,\$p'", $zipfile));

        // zip info
        $zipinfo = array();

        $filelist = explode("\n", $verbose);

        if($filelist){
            foreach($filelist as $rowdata){
                if($rowdata==''){
                    continue;
                }
                $rowdata = preg_replace('/[ ]{2,}/', ' ', $rowdata); // 将两个或以上空格替换为一个
                $tmp = array_slice(explode(' ', $rowdata), 1);       // 去掉第一个空格

                $file = array_combine($fields, $tmp);

                $zipinfo[$file['Name']] = $file['Length'].'_'.$file['CRC-32']; // 文件名，长度，CRC32，用于校验
            }
        }

        return $zipinfo;

    }


    /** 分析两个zip文件内容
    * @param  String $zipinfo1
    * @param  String $zipinfo2
    * @return Array
    */
    private function parse($zipinfo1, $zipinfo2){

        $result = array(
                'add' => array(),  // 新增
                'del' => array(),  // 缺少
                'match' => array() // 匹配
            );

        if($zipinfo1 && $zipinfo2){

            // 在zip1但不在zip2的文件
            $result['add'] = array_values(array_diff(array_keys($zipinfo1), array_keys($zipinfo2)));

            // 在zip2但不在zip1的文件
            $result['del'] = array_values(array_diff(array_keys($zipinfo2), array_keys($zipinfo1)));

            // 同时在zip1与zip2的文件
            $match_file = array_values(array_diff(array_keys($zipinfo1), $result['add']));

            // 检查相同文件名的文件内容是否匹配
            for($i=0,$len=count($match_file); $i<$len; $i++){

                if($zipinfo1[$match_file[$i]]==$zipinfo2[$match_file[$i]]){ // match
                    array_push($result['match'], $match_file[$i]);
                }else{  // not match, change to add
                    array_push($result['add'], $match_file[$i]);
                }

            }

        }

        return $result;

    }


    /** 检查zip文件是否正确
    * @param  String $zipfile zip文件
    * @return boolean
    */
    private function check($zipfile){
        // 文件存在且能解压
        return file_exists($zipfile) && shell_exec(sprintf('unzip -v %s | wc -l', $zipfile))>1;
    }


    /** 检查服务器是否有安装unzip
    * @return boolean
    */
    private function check_handler(){
        return strstr(shell_exec('unzip -v'), 'version')!='';
    }

} // class end

?>