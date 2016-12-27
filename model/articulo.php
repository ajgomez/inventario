<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2012-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'plugins/facturacion_base/model/core/articulo.php';

/**
 * Almacena los datos de un artículo.
 * 
 * @author Carlos García Gómez <neorazorx@gmail.com>
 */
class articulo extends FacturaScripts\model\articulo
{
   private static $search_tags;
   /**
    * Devuelve un array con los artículos encontrados en base a la búsqueda, modificado para listar todos los articulos
    * encontrados o usando FS_LIMIT.
    * @param type $query
    * @param type $offset
    * @param type $codfamilia
    * @param type $con_stock
    * @param type $codfabricante
    * @param type $bloqueados
    * @param boolean $limit Indica si queremos o no paginacion
    * @return \articulo
    */
   public function search($query='', $offset=0, $codfamilia='', $con_stock=FALSE, $codfabricante='', $bloqueados=FALSE, $limit = TRUE)
   {
      $artilist = array();
      $query = $this->no_html( mb_strtolower($query, 'UTF8') );
      
      if($query != '' AND $offset == 0 AND $codfamilia == '' AND $codfabricante == '' AND !$con_stock AND !$bloqueados)
      {
         /// intentamos obtener los datos de memcache        	
	if($this->new_search_tag($query) )
         {
            $artilist = $this->cache->get_array('articulos_search_'.$query);
         }
      }
      
      if( count($artilist) <= 1 )
      {
         $sql = "SELECT * FROM ".$this->table_name.' WHERE bloqueado = false';
         $separador = ' AND';
         
         if($codfamilia != '')
         {
            $sql .= $separador." codfamilia = ".$this->var2str($codfamilia);
         }
         
         if($codfabricante != '')
         {
            $sql .= $separador." codfabricante = ".$this->var2str($codfabricante);
         }
         
         if($con_stock)
         {
            $sql .= $separador." stockfis > 0";
         }
         
         if($bloqueados)
         {
            $sql .= $separador." bloqueado";
         }
         else
         {
            $sql .= $separador." bloqueado = FALSE";
         }
         
         if($query == '')
         {
            /// nada
         }
         else if( is_numeric($query) )
         {
            $sql .= $separador." (referencia = ".$this->var2str($query)
                    . " OR referencia LIKE '%".$query."%'"
                    . " OR partnumber LIKE '%".$query."%'"
                    . " OR equivalencia LIKE '%".$query."%'"
                    . " OR descripcion LIKE '%".$query."%'"
                    . " OR codbarras = '".$query."')";
         }
         else
         {
            /// ¿La búsqueda son varias palabras?
            $palabras = explode(' ', $query);
            if( count($palabras) > 1 )
            {
               $sql .= $separador." (lower(referencia) = ".$this->var2str($query)
                       . " OR lower(referencia) LIKE '%".$query."%'"
                       . " OR lower(partnumber) LIKE '%".$query."%'"
                       . " OR lower(equivalencia) LIKE '%".$query."%'"
                       . " OR (";
               
               foreach($palabras as $i => $pal)
               {
                  if($i == 0)
                  {
                     $sql .= "lower(descripcion) LIKE '%".$pal."%'";
                  }
                  else
                  {
                     $sql .= " AND lower(descripcion) LIKE '%".$pal."%'";
                  }
               }
               
               $sql .= "))";
            }
            else
            {
               $sql .= $separador." (lower(referencia) = ".$this->var2str($query)
                       . " OR lower(referencia) LIKE '%".$query."%'"
                       . " OR lower(partnumber) LIKE '%".$query."%'"
                       . " OR lower(equivalencia) LIKE '%".$query."%'"
                       . " OR lower(descripcion) LIKE '%".$query."%')";
            }
         }
         
         if( strtolower(FS_DB_TYPE) == 'mysql' )
         {
            $sql .= " ORDER BY lower(referencia) ASC";
         }
         else
         {
            $sql .= " ORDER BY referencia ASC";
         }
	   
         if ($limit) {
	  $data = $this->db->select_limit($sql, FS_ITEM_LIMIT, $offset);
	 } else {
	   $data = $this->db->select($sql);
	 }
         if($data)
         {
            foreach($data as $a)
            {
               $artilist[] = new \articulo($a);
            }
         }
      }
      
      return $artilist;
   }
/**
    * Comprueba y añade una cadena a la lista de búsquedas precargadas
    * en memcache. Devuelve TRUE si la cadena ya está en la lista de
    * precargadas.
    * @param type $tag
    * @return boolean
    */
   private function new_search_tag($tag)
   {
      $encontrado = FALSE;
      $actualizar = FALSE;
      
      if( strlen($tag) > 1 )
      {
         /// obtenemos los datos de memcache
         $this->get_search_tags();
         
         foreach(self::$search_tags as $i => $value)
         {
            if( $value['tag'] == $tag )
            {
               $encontrado = TRUE;
               if( time()+5400 > $value['expires']+300 )
               {
                  self::$search_tags[$i]['count']++;
                  self::$search_tags[$i]['expires'] = time() + (self::$search_tags[$i]['count'] * 5400);
                  $actualizar = TRUE;
               }
               break;
            }
         }
         if( !$encontrado )
         {
            self::$search_tags[] = array('tag' => $tag, 'expires' => time()+5400, 'count' => 1);
            $actualizar = TRUE;
         }
         
         if($actualizar)
         {
            $this->cache->set('articulos_searches', self::$search_tags, 5400);
         }
      }
      
      return $encontrado;
   }
   
}
