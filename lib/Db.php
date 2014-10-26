<?php
class Db {
    public function __construct($dsn, $user, $pass, $options = null) {
        $this->db   = new PDO($dsn, $user, $pass, $options);
    }

    public function query($sql) {
        return $this->db->query($sql);
    }

    public function count($table, $where = null) {
        if (!empty($where)) {
            $sql    = 'select * from '.$table.' where '.$where;
            $rs     = $this->query($sql);
            if ($rs && $rs->rowCount() > 0) {
                return $rs->rowCount();
            }
        }
        return 0;
    }

    public function fetch_one($sql) {
        $rs = $this->query($sql);
        if ($rs && $rs->rowCount() > 0) {
            $tmp    = $rs->fetch();
            return reset($tmp);
        } else {
            return false;
        }
    }

    public function fetch_all($sql) {
        $rs = $this->query($sql);
        if ($rs && $rs->rowCount() > 0) {
            return $rs->fetchAll();
        } else {
            return false;
        }
    }

    public function insert($table, $data) {
        $sql    = 'insert into `'.$table.'`(`'.join('`,`', array_keys($data)).'`) values("'.join('","', array_values($data)).'")';
        $this->query($sql);
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $where) {
        $sql    = 'update `'.$table.'` set ';
        foreach ($data as $key=>$val) {
            $sql    .= '`'.$key.'`="'.addslashes($val).'",';
        }
        $sql    = substr($sql, 0, -1);
        $sql    .= ' where '.$where;
        $rs = $this->query($sql);
        return $rs->rowCount();
    }

    public function insert_or_update($table, $data, $where) {
        if ($this->count($table, $where) > 0) { 
            return $this->update($table, $data, $where);
        } else {
            return $this->insert($table, $data);
        }
    }
}
