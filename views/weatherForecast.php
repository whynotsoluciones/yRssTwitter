<?php
/* SVN FILE: $Id: weatherForecast.php 9 2009-10-13 13:20:24Z Chris $ */
/**
 * Weather forecast view
 *
 * @filesource
 * @copyright    Copyright 2008 PBM Web Development - All Rights Reserved
 * @package      weatherForecast
 * @subpackage   weatherForecast.views
 * @since        V1.0.0
 * @version      $Revision: 9 $
 * @modifiedby   $LastChangedBy: Chris $
 * @lastmodified $Date: 2009-10-13 14:20:24 +0100 (Tue, 13 Oct 2009) $
 * @license      http://www.opensource.org/licenses/bsd-license.php The BSD License
 */

  $caption = $forecast->location->name . CHtml::tag('abbr', array('class' => 'geo', 'title' => $forecast->location->lat . ';' . $forecast->location->long), '') . ' Forecast';

  $thead = $tbody = array();
  $thead[0][] = '&nbsp;';
  $tbody[0][] = '&nbsp;';
  $tbody[1][] = '&nbsp;';
  $tbody[2][] = array(Yii::t('weatherForecast', 'Temp'), 'htmlOptions' => array('scope' => 'row'));
  $tbody[3][] = array(Yii::t('weatherForecast', 'Wind'), 'htmlOptions' => array('scope' => 'row'));
  $tbody[4][] = array(Yii::t('weatherForecast', 'Pressure'), 'htmlOptions' => array('scope' => 'row'));
  $tbody[0]['htmlOptions'] = array('class' => 'icons');
  $tbody[1]['htmlOptions'] = array('class' => 'description');
  $tbody[2]['htmlOptions'] = array('class' => 'temperature');
  $tbody[3]['htmlOptions'] = array('class' => 'wind');
  $tbody[4]['htmlOptions'] = array('class' => 'pressure');

  foreach ($forecast->days as $key => $day) {
    $thead[0][] = array($key == 0 ? Yii::t('weatherForecast', 'Current Conditions') : $day->date, 'htmlOptions' => array('scope' => 'col'));
    $tbody[0][] = CHtml::image($day->symbol, '');
    $tbody[1][] = Yii::t('weatherForecast', $day->description);
    $tbody[2][] = $key == 0 ?
      $day->temperature->value . '&#176;' . $day->temperature->units :
      Yii::t('weatherForecast', 'High') . ':' . $day->maxTemperature->value . '&#176;' . $day->maxTemperature->units . '<br/>' .
      Yii::t('weatherForecast', 'Low')  . ':' . $day->minTemperature->value . '&#176;'   . $day->minTemperature->units;
    $tbody[3][] = $day->windDirection->value . $day->windDirection->units  . ', ' . $day->windSpeed->value . $day->windSpeed->units;
    $tbody[4][] = $day->pressure->value . $day->pressure->units . ($key == 0 ? ', ' . $day->pressureTrend : '');
  } // foreach

  $tfoot[0][] = array(Yii::t('weatherForecast', 'Issued') . ": {$forecast->issued}", 'htmlOptions' => array('colspan' => $forecast->days->count + 1));
  $tfoot[1][] = array(Yii::t('weatherForecast', 'Data provided by ') . $forecast->provider, 'htmlOptions' => array('colspan' => $forecast->days->count + 1));

  echo $this->renderTable(compact('caption', 'thead', 'tbody', 'tfoot'), array('class'=>'weather-forecast'));
?>