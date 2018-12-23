<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

## Question
[stackoverflow:](https://stackoverflow.com/questions/43991673/use-spatial-search-to-count-the-total-number-of-my-users)





### Code

```

<?php

namespace App\Http\Controllers;

use Hamcrest\Thingy;
use Illuminate\Http\Request;
use Solarium\Client;
use Exception;

use App\Http\Requests\Solarium\QueryRequest;
use App\Http\Requests\Solarium\UpdateRequest;

/**
 * @resource Solr
 *
 * 操作Solr的api，提供地图搜索.
 */
class SolariumController extends Controller
{

    protected $client;

    public function __construct ( Client $client )
    {
        $this->client = $client;
    }

    public function update ( UpdateRequest $request )
    {
        try {
            $select = $this->client->createSelect();

            $select->addParam( 'q' , 'id:' . $request->phone );
            $select->setRows( 1 );
            $select->setResponseWriter( 'json' );

            //得到返回的数量
            $check = $this->client->select( $select )->getNumFound();

            //如果存在就更新
            if ( count( $check ) > 0 ) {
                $delete = $this->client->createUpdate();
                $delete->addDeleteQuery( 'id:' . $request->phone );
                $delete->addCommit();
                $result = $this->client->update( $delete )->getStatus();
                if ( $result !== 0 ) {
                    return response_fail();
                }
                unset( $delete , $result );
            }
            $add           = $this->client->createUpdate();
            $doc           = $add->createDocument();
            $doc->location = $request->location;
            $doc->id       = $request->phone;
            $add->addDocument( $doc );
            $add->addCommit();
            $result = $this->client->update( $add )->getStatus();

            unset( $add , $doc );

            if ( $result !== 0 ) {
                return response_fail();

            }
            return response_success();

        } catch ( Exception $e ) {
            return response_fail( $e->getCode() , $e->getMessage() );
        }
    }

    /**
     * @param              pt       ：圆的中心点的经纬度
     * @param              d        查找多大范围内的用户(km)：
     * @param              leftLl   左上角经纬度:格式是经度,纬度
     * @param              rightLl  右下角经纬度:格式是经度,纬度
     * @param QueryRequest $request
     */
    public function query ( QueryRequest $request )
    {
        try {
            //纬度是左右,经度是上下
            //左上角经纬度
            $leftLl = explode( ',' , $request->leftLl );
            //右下角经纬度
            $rightLl = explode( ',' , $request->rightLl );

            //分成多少列和行
            $count = 4;
            //执行分块
            $execResult = $this->splitQuadrate( $count , $leftLl , $rightLl );

            $select = $this->client->createSelect();
            $select->addParam( 'q' , '*:*' );
            $select->addParam( 'fq' , '{!geofilt}' );
            $select->addParam( 'sfield' , 'location' );
            $select->addParam( 'pt' , $request->pt );
            $select->addParam( 'd' , $request->d );
            $select->addParam( 'facet' , 'true' );
            $select->setRows( 0 );
            $select->setResponseWriter( 'json' );

            $facetSet = $select->getFacetSet();
            foreach ( $execResult as $k => $v ) {
                $facetSet->createFacetQuery( $k . '_location' )->setQuery( vsprintf(
                    'location:[%s TO %s]' , [ $v['left_bottom'] , $v['right_top'] ]
                ) );
            }
            $res = $this->client->select( $select )->getData()['facet_counts']['facet_queries'];


            $data = [];
            foreach ( $res as $k => $v ) {

                if ( empty( $v ) ) {
                    unset( $res->$k );
                    continue;
                }

                $data[] = [
                    'center' => $execResult[ intval( $k ) ]['center'] ,
                    'count'  => $v ,
                ];
            }

            return response_success( $data );

        } catch ( Exception $e ) {
            return response_fail( $e->getCode() , $e->getMessage() );
        }


    }

    /**
     * 切割正方形
     *
     * @param $leftLl  左上角经纬度
     * @param $rightLl 右下角经纬度
     *
     * @return array
     */
    public function splitQuadrate ( $count , $leftLl , $rightLl )
    {
        //计数
        $math = 1;

        //左右相减
        $right_left_difference = ( $rightLl[1] - $leftLl[1] ) / $count;

        //上下相减
        $top_bottom_difference = ( $leftLl[0] - $rightLl[0] ) / $count;

        //region 将正方形划分成多个正方形
        for ( $i = 0 ; $i <= $count ; $i++ ) {

            //得出上下的差异
            $top = six_floor( $leftLl[0] - $i * $top_bottom_difference );

            for ( $j = 0 ; $j <= $count ; $j++ ) {

                $result[ $math ] = $top . ',' . six_floor( $leftLl[1] + $j * $right_left_difference );

                $math++;
            }


            if ( $math > ( $count * 2 ) ) {

                //假设分为4块，但是4块其实是有5个经纬度的
                $llCount = $count + 1;

                for ( $s = 0 ; $s < $count ; $s++ ) {

                    $tmp = $math - $llCount + $s;


                    //临时用来计算中心点的变量
                    $tmp_left_bottom = explode( ',' , $result[ $tmp ] );

                    $tmp_right_top = explode( ',' , $result[ $tmp + 1 - $llCount ] );

                    //中心点的经度=右上角的经度减去左下角的经度
                    $center_longitude = six_floor( $tmp_left_bottom[0] + ( ( $tmp_right_top[0] - $tmp_left_bottom[0] ) / 2 ) );
                    //中心点的纬度=左下角的纬度减去右上角的纬度
                    $center_latitude = six_floor( $tmp_right_top[1] + ( ( $tmp_left_bottom[1] - $tmp_right_top[1] ) / 2 ) );

                    $exeResult[] = [

                        //左上
//                        'left_top' => $result[ $tmp - $llCount ] ,

                        //左下
                        'left_bottom' => $result[ $tmp ] ,

                        //右上
                        'right_top'   => $result[ $tmp + 1 - $llCount ] ,

                        //计算中心点
                        'center'      => $center_longitude . ',' . $center_latitude ,

                        //右下角
//                        'right_bottom' => $result[ $tmp + 1 ] ,
                    ];

                }
            }
        }

        return $exeResult;

        //endregion
    }
}

```
