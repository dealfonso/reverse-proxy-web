<?php

class Mappings {
    static function FromFile($fichero) {
        try {
            $mapeos = @file_get_contents($fichero);
            if ($mapeos === false)
                throw new Exception("El fichero $fichero no existe");
            $mapeos = json_decode($mapeos, true);
            return new Mappings($mapeos);
        } catch (\Exception $e) {
            return false;
        }
    }
    function __construct($mapeos = []) {
        $this->_mapeos = [];
        foreach ($mapeos as $dns => $entrada) {
            if (gettype($entrada) == 'string') {
                $entrada = [ "ip" => $entrada ];
            }
            $this->add($dns, $entrada["ip"]??null, $entrada["owner"]??null, $entrada["portmap"]??[], $entrada["comment"]??null);
        }
    }
    function add($dns, $ip, $owner = null, $portmap = [], $comment = null) {
        $portmap_f = [];

        // Preparamos el mapeo de puertos por defecto
        if (count($portmap) == 0) {
            $portmap = [
                "80" => 80,
                "443" => 443,
            ];
        }

        // Calculamos el mapeo de puertos
        foreach ($portmap as $s => $d) {
            if (is_numeric($s)) {

                $v_s = intval($s);
                if (! in_array($v_s, __PUERTOS_SOPORTADOS)) {
                    p_error("Ignoring port $v_s: Only ports " . implode(", ", __PUERTOS_SOPORTADOS) . " are currently supported");
                    continue;
                }

                if ($d !== null) {
                    if (!is_numeric($d)) {
                        p_error("Ignoring invalid destination port " . $d);
                        continue;
                    }
                    $d = intval($d);
                }

                $portmap_f[$s] = $d;
            } else {
                p_error("Ignoring invalid source port " . $s);
                continue;
            }
        }

        // Nos aseguramos de que todos tienen valor
        foreach (__PUERTOS_SOPORTADOS as $port) {
            if (!array_key_exists($port, $portmap_f)) {
                $portmap_f[$port] = null;
            }
        }

        $ip = trim($ip);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            p_error("Ignoring invalid IP address " . $ip);
            return false;
        }

        $this->_mapeos[trim($dns)] = [
            "ip" => trim($ip),
            "owner" => trim($owner),
            "comment" => trim($comment),
            "portmap" => $portmap_f
        ];
        return true;
    }
    function to_json($fichero) {
        try {
            $contenido = json_encode($this->_mapeos);
            $result = @file_put_contents($fichero, $contenido);
            if ($result === false) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    function gen_portmaps($fichero, $ports) {
        $error = false;
        foreach ($ports as $port) {
            $c_fichero = sprintf($fichero, $port);
            if (! $this->gen_portmap($c_fichero, $port)) {
                $error = true;
                p_error("Failed to create file $c_fichero");
            }
        }
        return !$error;
    }

    protected function _gen_nginx_config($lineas, $port) {
        $contenido = [ "map \$http_host \$server_$port {" ];
        $contenido = array_merge($contenido, $lineas);
        array_push($contenido, "}");
        return implode("\n", $contenido);
    }

    function gen_portmap($fichero, $port_g) {
        try {
            $mapeos = [];
            foreach ($this->_mapeos as $dns => $e) {
                foreach ($e['portmap'] as $port => $map) {
                    if (($port == $port_g) && ($map !== null)) {
                        array_push($mapeos, "$dns {$e["ip"]}:{$map}; # " . $e["owner"]??"");
                    }
                }
            }

            $contenido = $this->_gen_nginx_config($mapeos, $port_g);

            $result = @file_put_contents($fichero, $contenido);
            if ($result === false) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    function remove($dns, $ip) {
        if (isset($this->_mapeos[$dns]) && ($this->_mapeos[$dns]["ip"] === $ip)) {
            unset($this->_mapeos[$dns]);
            return true;
        }
        return false;
    }
    function get($dns, $ip = null) {
        if (!isset($this->_mapeos[$dns])) return false;
        if ($ip !== null) {
            if ($this->_mapeos[$dns]["ip"] !== $ip) return false;
        }
        return $this->_mapeos[$dns];
    }
    public function to_html($owner = null) {
        $resultado = [];
        foreach ($this->_mapeos as $dns => $e) {
            array_push($resultado, $this->entrada($dns, $e, $owner));
        }        
        return implode("\n", $resultado);
    }

    protected function entrada($dns, $e, $owner = null) {

        $propietario = $e["owner"]??$owner;
        if ($propietario === "") $propietario = $owner;

        if ($propietario === $owner)
            return <<<EOF
            <form method="post">
                <div class="col">
                    <div class="p-3 entrada shadow-sm row">
                        <div class="py-3">
                            <a href="//$dns" target="_blank">
                                <span class="dns-name">$dns</span>
                            </a>
                            <i class="material-icons">
                                navigate_next
                            </i>
                            <span class="ip-address">{$e['ip']}</span>
                            <br/>
                            <div class="small">
EOF .
($e['portmap']['80']!==null?"<span class=\"me-3\"><i class=\"material-icons-outlined me-1\">http</i>{$e['portmap']['80']}</span>":"") .
($e['portmap']['443']!==null?"<span class=\"me-3\"><i class=\"material-icons-outlined me-1\">https</i>{$e['portmap']['443']}</span>":"") .

<<<EOF
                            </div>
                            <input type="hidden" name="dns_name" value="$dns">
                            <input type="hidden" name="suffix" value="">
                            <input type="hidden" name="ip_address" value="{$e['ip']}">
                            <input type="hidden" name="http_port" value="{$e['portmap']['80']}">
                            <input type="hidden" name="https_port" value="{$e['portmap']['443']}">
                        </div>

                        <div class="text-end border-top pt-3">
                            <a type="submit" role="button" name="edit" class="btn btn-link" onclick="javascript:editdata(this)">
                                <i class="material-icons">
                                    edit
                                </i>
                            </a>
                            <button type="submit" name="eliminar" class="btn" data-confirm="¿Esta seguro de que quiere eliminar la redireccion?">
                                <i class="material-icons">
                                    clear
                                </i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            EOF;
        else
            return <<<EOF
            <div class="col">
                <div class="p-3 entrada shadow-sm row no-owner">
                    <div class="py-3">
                        <a href="//$dns" target="_blank">
                            <span class="dns-name">$dns</span>
                        </a>
                        <i class="material-icons">
                            navigate_next
                        </i>
                        <span class="ip-address">{$e['ip']}</span>
                        <br/>
                        <div class="small">
EOF .
($e['portmap']['80']!==null?"<span class=\"me-3\"><i class=\"material-icons-outlined me-1\">http</i>{$e['portmap']['80']}</span>":"") .
($e['portmap']['443']!==null?"<span class=\"me-3\"><i class=\"material-icons-outlined me-1\">https</i>{$e['portmap']['443']}</span>":"") .

<<<EOF
                        </div>
                    <input type="hidden" name="dns_name" value="$dns">
                        <input type="hidden" name="suffix" value="">
                        <input type="hidden" name="ip_address" value="{$e['ip']}">
                        <input type="hidden" name="http_port" value="{$e['portmap']['80']}">
                        <input type="hidden" name="https_port" value="{$e['portmap']['443']}">
                    </div>
                    <div class="text-end border-top pt-3">
                        <a type="submit" role="button" name="edit" class="btn btn-link" onclick="javascript:editdata(this)">
                            <i class="material-icons">
                                edit
                            </i>
                        </a>
                    </div>
                </div>
            </div>
            EOF;        
    }
}
