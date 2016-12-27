<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2016  Carlos Garcia Gomez      neorazorx@gmail.com
 * Copyright (C) 2016  Luismipr                 luismipr@gmail.com
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

/**
 * Description of valoracion_inventario
 *
 * @author ajgomez
 */
require_model('articulo.php');
require_model('stock.php');

class valoracion_inventario extends fs_controller{
   public $listado;
   PUBLIC $totalizacion;
   
   
  public function __construct() {
      parent::__construct(__CLASS__, 'Valoracion Stock', 'ventas', FALSE, FALSE);
  }
  
  protected function private_core() {
    $this->shared_extensions();
    $this->totalizacion = 0;
    
    $stock = new articulo();
    $this->listado = $stock->search('', 0, '', TRUE, '', FALSE, FALSE );
    
    if (isset($_REQUEST['descargar'])) {
      $this->excel();
    }
  }
  
  /* Exporta el listado de valoracion en CSV
   * 
   */
  private function excel()
   {
     $total_coste = 0;
     $total_pvp = 0;
     
      $this->template = FALSE;
    
      header("content-type:application/csv;charset=ISO-8859-1");
      header("Content-Disposition: attachment; filename=\"Valoracion_stocks.csv\"");
      echo "REFERENCIA;DESCRIPCION;STOCK;COSTO;TOTAL COSTO;PVP;TOTAL PVP\n";
      
      foreach($this->listado as $d)
      {

        echo "'". $d->referencia.";";
	$dos = html_entity_decode(mb_convert_encoding($d->descripcion, "ISO-8859-1", mb_detect_encoding($d->descripcion, "UTF-8, CP850, ISO-8859-15", true)));
        echo $dos.";";
        echo number_format($d->stockfis, 2, ',', '').";";
        echo number_format($d->preciocoste, 2, ',', '').";";
        echo number_format($d->preciocoste * $d->stockfis, 2, ',', '').";";
        echo number_format($d->pvp, 2, ',', '').";";
        echo number_format($d->pvp * $d->stockfis, 2, ',', '')."\n";
	 
	 $total_coste += $d->preciocoste * $d->stockfis;
	 $total_pvp += $d->pvp * $d->stockfis;
      }
      
      echo ";";
      echo ";";
      echo "TOTALES;";
      echo ";";
      echo number_format($total_coste, 2, ',', '').";";
      echo ";";
      echo number_format($total_pvp, 2, ',', '')."\n";
      
   }
   
  public function shared_extensions() {
    $ext = new fs_extension();
    $ext->from = __CLASS__;
    $ext->name = "valoracion_stock";
    $ext->to = "ventas_articulos";
    $ext->type = "tab";
    $ext->text = "<span class=\"fa fa-euro\"></span> Valoracion";
    if( !$ext->save() ) {
      $this->new_error_msg("Error al grabar la extension");
    }

  }
   
}
