<?php

function kpindexsmt_get_data($datatype)
{
  if ($datatype=="geomagneticforecast")  {   $apiurl = 'https://services.swpc.noaa.gov/text/3-day-geomag-forecast.txt';    }
    // Requete
    $response = wp_remote_get( $apiurl, array());
    $response_code = wp_remote_retrieve_response_code( $response );
    if ($response_code<>200)
    {
      return array('L\'appel aux données (API) à retourné une erreur http '.$http_code);
    } else {
      return kpindexsmt_convert_txt_to_array($response['body'],$datatype);
    }
  }

  function kpindexsmt_convert_txt_to_array($data,$datatype)
  {
    $arrayout=array();
    $txtlines=explode("\n",$data);

    if ($datatype=="geomagneticforecast")
    {
      $datetimepub = $txtlines[1];
      $datetimepub = str_replace(':Issued: ','',$datetimepub);
      $datetimeparts = explode(' ',$datetimepub);
      $datetimepub = $datetimeparts[2]." ".$datetimeparts[1].' '.$datetimeparts[0]." ".substr($datetimeparts[3],0,2).":".substr($datetimeparts[3],2,2);
      $arrayout['dt']=strtotime($datetimepub);
      $daterange = $txtlines[16];
      $arrayout['dates']['start'] = strtotime(substr($daterange,12,8)." ".date("Y"));
      $arrayout['dates']['stop'] = strtotime(substr($daterange,32,8)." ".date("Y"));
      $cptforecastline = 17;
      while ($cptforecastline<25)
      {
        $thisline = $txtlines[$cptforecastline];
        $arrayout['values'][0][]=intval(substr($thisline,14,3));
        $arrayout['values'][1][]=intval(substr($thisline,24,3));
        $arrayout['values'][2][]=intval(substr($thisline,34,3));
        $cptforecastline++;
      }
    }


    //  $arrayout['txtlines']=$txtlines;
    return $arrayout;
  }

  //  Widget

  class kpindexsmt_widget_class extends WP_Widget {

    // Main constructor
    public function __construct() {
      parent::__construct(
        'kpindex_widget',
        __( 'Affichage des prévisons géomagnétiques (KP)', 'kp-index-smt' ),
        array(
          'customize_selective_refresh' => true,
        )
      );
    }

    // The widget form (for the backend )
    public function form( $instance ) {

      // Set widget defaults
      $defaults = array(
        'title' => '',
        /*  'display' => 'default', */
        'show_icon' => 1,
        'show_value' => 1,
        'show_hour' => 1
      );

      extract( wp_parse_args( ( array ) $instance, $defaults ) );

      ?>
      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Titre du Widget', 'kp-index-smt' ); ?> : </label>
        <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
      </p>

      <p>
        <label for="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>"><?php _e( 'Afficher', 'kp-index-smt' ); ?> : </label>
        <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_icon' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_icon' ) ); ?>" value="1" <?php checked( '1', $show_icon ); ?>> Icones&nbsp;&nbsp;|&nbsp;
        <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_value' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_value' ) ); ?>" value="1" <?php checked( '1', $show_value ); ?>> Valeurs&nbsp;&nbsp;|&nbsp;
        <input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'show_hour' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_hour' ) ); ?>" value="1" <?php checked( '1', $show_hour ); ?>> Heures
      </p>


      <?php

    }

    // Update widget settings
    public function update( $new_instance, $old_instance ) {
      $instance = $old_instance;
      $instance['title']    = isset( $new_instance['title'] ) ? wp_strip_all_tags( $new_instance['title'] ) : '';
      $instance['show_icon'] = isset( $new_instance['show_icon'] ) ? 1 : false;
      $instance['show_value'] = isset( $new_instance['show_value'] ) ? 1 : false;
      $instance['show_hour'] = isset( $new_instance['show_hour'] ) ? 1 : false;
      return $instance;
    }

    // Display the widget
    public function widget( $args, $instance ) {

      extract( $args );

      global $kpindexsmt_pluginwebpath,$warningcolors;

      // Check the widget options
      $title    = isset( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
      $show_icon = ! empty( $instance['show_icon'] ) ? $instance['show_icon'] : false;
      $show_value = ! empty( $instance['show_value'] ) ? $instance['show_value'] : false;
      $show_hour = ! empty( $instance['show_hour'] ) ? $instance['show_hour'] : false;

      // WordPress core before_widget hook (always include )
      echo $before_widget;

      // Display the widget
      echo '<div class="widget-text wp_widget_plugin_box">';

      // Display widget title if defined
      if ( $title ) {
        echo $before_title . $title . $after_title;
      }

      $forecast = kpindexsmt_get_data('geomagneticforecast');
      //  var_dump($xray);

      if (isset($forecast['dt']))
      {
      $htmlout='<table width="100%">';
      $cptday=0;
      $dateformat = get_option('date_format');
      $loopdate = time();
      $loopdate = new DateTime(date("Y-m-d"));
      while ($cptday<3)
      {
        $dayetiq = false;
        if ($cptday==0) {    $dayetiq=__('Aujourd\'hui','kpindexsmt');     }
        if ($cptday==1) {    $dayetiq=__('Demain','kpindexsmt');     }
        if ($dayetiq==false)
        {
          $htmlout.='<tr><td colspan="8" style="padding:1px;">'.$loopdate->format($dateformat).'</td></tr>';
        } else {
          $htmlout.='<tr><td colspan="8" style="padding:1px;">'.$dayetiq.'</td></tr>';
        }
        if ($show_hour===1)
        {
          $htmlout.="<tr>";
          $cpthour=0;
          $bgcellab = "a";
          while ($cpthour<8)
          {
            $htmlout.='<td class="kpindexsmt_widget_table_cell_'.$bgcellab.'" style="padding:1px;">'.($cpthour*3).'</td>';
            $cpthour++;
            if ($bgcellab=="a") {   $bgcellab="b";  } else {  $bgcellab="a";  }
          }
          $htmlout.="</tr>";
        }
        if ($show_icon===1)
        {
          $bgcellab = "a";
          $htmlout.="<tr>";
          foreach ($forecast['values'][$cptday] as $key => $value)
          {
            $thislevel=$value-5;
            if ($thislevel<0) {  $thislevel=0;  }
            if ($cptday==0&&date('G')>(intval($key)*3)+3)
            {
              $htmlout.='<td class="kpindexsmt_widget_table_cell_'.$bgcellab.'" style="padding:1px;">&nbsp;</td>';
            } else {
              $htmlout.='<td class="kpindexsmt_widget_table_cell_'.$bgcellab.'"';
              if ($thislevel>0)
              {
                $htmlout.=' style="padding:1px;border-bottom:2px solid '.$warningcolors[$thislevel].'"';
              } else {
                $htmlout.=' style="padding:1px;"';
              }
              $htmlout.='><img src="'.$kpindexsmt_pluginwebpath.'/images/warning_'.$thislevel.'.png" style="margin:0px;" class="kpindexsmt_widget_icon"></td>';
            }

            if ($bgcellab=="a") {   $bgcellab="b";  } else {  $bgcellab="a";  }
          }
          $htmlout.='</tr>';
        }
        if ($show_value===1)
        {
          $bgcellab = "a";
          $htmlout.="<tr>";
          foreach ($forecast['values'][$cptday] as $key => $value)
          {
            if ($cptday==0&&date('G')>(intval($key)*3)+3)
            {
              $htmlout.='<td class="kpindexsmt_widget_table_cell_'.$bgcellab.'" style="padding:1px;">&nbsp;</td>';
            } else {
              $htmlout.='<td class="kpindexsmt_widget_table_cell_'.$bgcellab.'" style="padding:1px;">'.$value.'</td>';
            }
            if ($bgcellab=="a") {   $bgcellab="b";  } else {  $bgcellab="a";  }
          }
          $htmlout.='</tr>';
        }
        $loopdate->modify('+1 day');
        $cptday++;
      }

      $htmlout.='<tr><td colspan="8" style="padding:1px;" class="kpindexsmt_widget_credit">UTC - Source : <a href="https://www.swpc.noaa.gov/" target="_blank">swpc.noaa.gov</a></td></tr>';
      $htmlout.='</table>';
    } else {
      $htmlout='<div class="kpindex_widget_error_maindiv">Une erreur est survenue pendant l\'appel aux données.</div>';
    }
      echo $htmlout;

      echo '</div>';

      // WordPress core after_widget hook (always include )
      echo $after_widget;


    }

  }


  ?>
