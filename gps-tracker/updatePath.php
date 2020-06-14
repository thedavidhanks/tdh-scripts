<?php

//Trip has a name and an array of tripLeg objects.
class Trip {
    public $name;
    public $tripLegs = [];

    function add_leg($leg){
        array_push($this->tripLegs, $leg);
    }
}

//Travel is an array of trip objects.
class Travel{
    public $trips = [];
    
    function add_trip($trip){
        array_push($this->trips, $trip);
    }
    
}

//TripLeg describes stages of the trip.
class TripLeg {
  public $id;
  public $startLatLong;
  public $startTime;
  public $endLatLong;
  public $distance;
  public $MQencodedPath;
}

function getMQroute($point1, $point2){
    //returns a json from MapQuest given $point1 [ lat, long ] & $point2 [lat, long]
    
    $apiKey = getenv("MQ_API");
    //echo "API key: ".$apiKey;
    $url = "http://open.mapquestapi.com/directions/v2/route?key=$apiKey";
    //$data = array('key1' => 'value1', 'key2' => 'value2');

    //For json format see https://developer.mapquest.com/documentation/open/directions-api/route/post/
    $jsonData = 
        "{
            'locations' : [
                      {'latLng': {
                        'lat': $point1[0],
                        'lng': $point1[1]
                      }},
                    {'latLng': {
                        'lat': $point2[0],
                        'lng': $point2[1]
                      }}
            ],
            'options' : {
                'narrativeType' : 'none',
                'shapeFormat' : 'cmp',
                'generalize' : 0,
                'timeType' : 1,
                'highwayEfficiency' : 16.0
            }
        }";
    
    $options = array(
        'http' => array(
            'header'  => "Content-Type: application/json",
            'method'  => 'POST',
            'content' => $jsonData
        )
    );
    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    if ($result === FALSE || empty($result)) { 
        //echo "Nothing returned from map quest";
        $result = FALSE;
    }
    //echo $result;
    return $result;
}
function addPointToPath($newLatLong, $trip) {
    //Adds on to the existing path with the new coordinate
    $jsonRouteFilePath = "cricketTraveledPath.json";
    $error_msg = "";
    $result = false;
    
    //read the old $lat and $long from the $jsonTravelFilePath
    $tripPaths = json_decode(file_get_contents($jsonRouteFilePath));    
    $lastLeg = end($tripPaths->{$trip});
    $lastLatLong = $lastLeg->{"endLatLong"};
    $lastid = $lastLeg->{"id"};
    
    //get a new encoded path from MapQuestAPI
    $mqString = getMQroute($lastLatLong, $newLatLong);
    if($mqString){
        $mqResult = json_decode($mqString);
   
        //create a new Leg php object
        /*  looks like this in json format
        {  
            "id": 40,
            "startLatLong": [ lat ,long]
            "endLatLong": [31.9494000, -104.6993000],
            "distance": 600.433,
            "MQencodedPath": "}yvwEh_prQFwB??NoK??\\iELiDBy@???G?y@@sEAa@??G?kA?E???A`@CrG??BbABlGEnCIjCKnBGnBYjF?xBFvB`@~DRpBNpAFt@H`BDrA@d@C`GAdBGbCAtDAfA?jA?dG?jB?~@?T?xC?`CAbE?~D?pK?`H?~C?vA?`@?L?b@?fA?`D?lC?lC?vA?dB?`H@~K?rHA~B@hH?^?zE?|E?`FBzEH~E@jF?`B?lABnW?|A?fI?|A?nD?|A?zB@jT?xA?h@?xEAjC?rB@bFCxCGdBUvCUhBs@~FWpBa@|CS~AkAhI{@lGS~AGx@[fCeBlM{@nGOpAWxBg@zDoA|JSbCS|BUpFGtCC|D@vD?pBBtLBdI?pBDxL@pKBjHBdBLjI@vEDdQCff@?~A]xb@G|P?tPBd]Gx\\E~LA~L?xAArTFfFRhF\\`Fb@|EpAtHfB|HhAvDzCzJtIbYjFtPdFhPVz@x@fDj@rDf@fFJ|E?hD?nOBfTAzN@|L?V?tI?rCExBMlCGpEDhBArQ?bA?pHAbI@bICzFAfA@lDClC@vH?jI@`@CvC?rCElBG~G?fTAdRBtL?zJ@t\\AlQ?pQ?v_@?|B@|C?jB?`HBrRAvc@@xQ@zPAfd@??^?~a@@??DrALn@tApEVhAPjAFpCJtBBre@lC``CB|B?R|@ds@LtOArS_@lSqAhf@mB`v@YlOSdVD~JP|N|Bfx@Rv`@i@dc@_Ap\\IjKGpS@pnA@pc@AtCBte@@|d@?zBKfNq@xJMdAmAdJ}ExR{HpX}HhYsHfXqBtHqEdPwCdLqCnNmA|IkAdKsAhQsBbVoClVqD~VkElVqExTuD`PuO`l@qFfTeGxTgGpU{\\ppAeBtIcAhHm@lHYrHGhSF~PApV@|_AHb{BJdZd@j\\n@pZZpWBzTObR}@p^[hUIx[D`UA|i@QlQy@db@wAhd@cAv`@UpULtU`@hQd@zODjAzA`h@~@p_@\\jT?j\\F~N@z`ABrJ?vBFd^Dzi@E~L[|Oq@`LsAbMsJpv@mMjbAoI~m@eAxIeEd\\yC~Ts@vJUlJPdIp@tIpA~HnBtItCxHx]rs@lXdj@~GfNrC`G|AvDz@~B|@tCnBdIrArHdAxJ^vGNtHEfFOxGk@zIUpBy@`GsBdMiXndBu@jFgAjKc@rG]bHO`HG|NCt|@AtV?hAAv_@C`NEzwA@nGIdgA?fAAzXQtNOdISlIWvHI~GE`I@pFLdL`@|NPhLD~CHhLGv}CQpY]pVCtM?dIXhWPd[Adu@AdSMvrHOvN[zM_@tIo@zKg@|Fu@hIo@zFuAlKgB~KO`A_Hba@{BfN}CxQqAzIe@tDiAtKaA|M]vGc@pMMpJErHBhVBpe@@lA@~p@?~A@xUCbKB~^ApO@zn@G~lCBfgBA~M@lEAnYBtGCdDBb]BfE?|d@CvDBdFC`HDvT@b`@B~DTdERfBh@rDh@`Cz@pCtB`FzAnCxHrMjM|SvDlFtBdCjAlA\\TtE|D|CrCzF~E~@r@rBrBpBhCdAbBfBfD~@fCp@vB\\nAZbBj@dEPxBJhCHpHBtSA|V@~A?`PDzHN|JDlBl@zKR~C|@jK~@`L^nGH`EB~FK|GGtAUjEqC`[mA|Nw@hNOjEOzIK|z@?dr@EpMMjdC?xAc@t{D?hSGn]DlIJ`HXbINvCf@tGVlC`A`JtAfJnDxSt@rEvGp_@hEbWv@jE|@bHt@fHb@`Gb@vJTbIHzK@fSAfk@Exo@C~CBzNMdsDRpLXjHj@rIV`Cx@hHb@~CnArHRl@l@dCn@rC|@zCt@dCnBdGnCvHpAdD~@rCfAtC`E~KrGdQnArDbF`NhArCxU|o@lDnJzDdLfAbEp@rCj@vC`@dCd@rD\\xDRpCVvFDfD@|EItEeAx\\QdFMdFGnECrEBdHLxH^rJZxE`@tFvAvMbAbJl@fF^fDdD`ZrBpQjD`[TxB\\fD~AbNbBdLhCjLt@|CtBdHxAlEhDvIjDrHHPfD`GrBdDvGfJvFrH|EfHzEbIpC~FhC|F|@~BvD~KzBrIpAvFx@rEHVz@dFhA|In@zHNbCLpARdETtHF|FE~_B?pU?bB?rUCb]?npAE`KAfj@Cv~A@|IClM@rXA~ZBjENjFN`DRjCp@lG~@bGXpAZzA|@tDfD|KbA|ChEhMhXvy@fAtCzEjLhB|DlEfIvCvExEbHxApBnG|HtI~J`HlIpAxAp@n@fDfEhFfGvBfCfAjAhAxA~DtEp@|@vCfD|InKdAhArAbBdF~FdFfGtAxAnHdJlCrDjEpG~@zApDtGf@hAj@`AzCvGrB~Er@hBzCdJxAzEjCvJ|@~DdAlFbBlKjEp]^pEf@rEdBfMb@vDrBhLhAlFbC~JlAtEdEvLxBvFfDvHzBtElU~c@xCbGzPn\\vGzMdBfEv@jBfCnHh@jBx@rChBhHrAhG`AdGdAbId@~D`@nE`@dGZhHNnG@tI@vnB@nTAlo@KbHQfDc@lF_@rDc@zCcApFy@jDwBnI{B`Ie@lBuBrI}@dEm@hDeAfHe@`Ey@~IWtFGnBMdJ@nIFdEBx@FdCNhCl@jIZ`DZlCbAnHx@|DR~@jAnFx@|C|EnOfAxDrBdJ|@dGVnBd@|FPhDLjEDh_@NzqBDvgACtILvlBDbEPbFLdB\\`ED`@~@~G\\fBl@jCh@rBbCnIrArDxAjDpDnG|A~BjA`B|DtEj@p@pMpMj@h@rD~DtHtHpCvCrJxJb@d@~TtUlMtMz@~@jd@ne@jKnKrDxD`DlDzD|DzAbBrBhCdCnDdAbBzBfEdA~B~@|Bz@`C`AvClAxE^dB`AzEXdBl@rFb@xFNpCFfBDnC?|PAlHBdz@CvK@hKAfb@KfJChJIdFEdHu@dWGtDOjDO|EUvKCzAG~ECrECxE@tIFdHL|G\\tKh@hSLrDR~LVjTBrG@xIBxGArODv\\?rkB?nSAjAB~GC|GDpFn@xI^`CnAxE`AzC`D|HbBxDpFnLtBlErQpa@bBfEbBxDxLdXz@vBrHjPrQva@pFvLbDjGfCjEzSp\\tWha@lDvFzEnHnIdMhBdDnEvIpGhNrBzEfCnFtGdOdChFtCzGrClGnHtPdDfHtg@jiAfAdC|CtF|CnFfHvKhB`CpD`Fn@t@dC~BxNzL`OrLdDfCpYpU|PbNfG`EvChBvC`BtIfEbGlCbA^xAl@zCfAjElAxBp@`Dx@`Dr@jI`BpEv@zGtAtH`Bf]fHvFlArPbD~KbClAVdF~@|RhErBd@tQnDdIjBlGjA~PlDlHdBfFbBxD|A`EhBvDlBjAr@vCjB~EvDhGfF`CdClBtBvClDxCdElDtFxCtFlBbE~ArDlCnHXz@x@jCbBfGfBhIf@pCn@vEp@pEv@lINvBVdEJpDJlF@fHGloBEfJO~rDDzI^bRb@xMj@hLjApPj@xGn@rG~@hI~@rHjAdIfAjHzAdIbBhIlCfLxEnR`ChIzCjJrBvFrB|FlCjHbDtHjL|Vz@rBvJvSpCrG~BzExIzRj@pApHnOxApDrHxOvHnPr@hBhAzBnIxQpDdIpB~EtAhDzBhG~BjH~BbI`BtGdBrH|@vEzA~I~@|FjApJr@rH|@~Kb@bI\\jIH|FFbHBpEElJF~bAFvPPtQPxKNfGv@tVd@xMJpAdAzTnArTdAzTPrDtB``@~Ah\\x@bXh@nWLjJNxX?bZEzZEh[?hC?vSAnBQveD@zKE|UApV?dACbQK|CS|JSzG_@fJgAbR]hDq@dIgArKyPpxA]xDkA|Og@|J]dIOxEQpNErRN`MRhGx@lRbSfrCr@~Jv@bMJjBnAbVBzBl@rQNjGJnMJvpAA`IBrlABf]Aj\\FpcC?fxACjJ?|BCjzGEf}@Bjm@EfrBEft@Bxg@CjqBPr}CIpGMdGItBSzCWzC[xC_@vC[xAo@jF}Mr_AoDbWO`BYzEQbFExCAtCBlDRtGhA`QbBtYz@tMPfDr@tKXhFp@jKjBzXz@`Lb@vGV~DzCda@^zFz@fL^hHJ`EDjJ@dYCve@IxOCv[?buAGzTAhfABdMCfFBj^@`JDvb@Ilc@O~EUnBg@rDgBfLUfDiEzjAgAf[E~B?tBD|BPvD\\dFXrBh@rC`AdEfCzJvA|GTxATjCN`DB`A@xGC~cAOfh@GpH?tDCvHBvHAxh@F`UHxHJxQ?jQAzX?l^?pZDnf@Ad}@Uxm@EnYJ~j@LbRBtU?jqACva@BdXC`WAd_@Sh^?z]F|LNnHR~Nb@lVDnNE`IAnn@A`r@N|tBFtYDhy@@bE?tGMlM]fSOhHClCEl_@@xICby@GhF[|G[`EoBvYSzCiD|d@s@zKI|E?~F\\fJp@dG^zBh@xCf}@bgEnAlH\\xCXzCRzCN|CxKdoCnBvf@|A`_@lAj\\|Ab^HdD?dDMvFUdDI|@cBtM_DvSa@bEGbAKxCGrD@hw@@nBNtCR~CPbBp@|EbCvOvCpRt@hFd@~Ej@lHJfKA`DIvFItBOvB[tD{@fHoF|ZgA|GY|CSzDC|ADfEFbCj@xF|@rE`@bBnApDr@dBx@`BxB`Dt@bAvDtD|ErEfFnErFhGlA`BlBtC~DnHxAdDjBfFt@pCxF`Qh@`BbFdPb@rAhCbGfA`CjBhDtAzB~DrFxBpCfL~NtNrR`AlAbNxPdB~BxD`GrBjEz@jBhOd^zNv]hLhXvCfJd@lB~AjIf@rDr@zHXdEPnGClJIrCcAbNaBzOeAbK_Fdd@k@`GqBfYqDbf@a@|FYlGKtEA~ADlGP`Fp@zJd@xDNbAp@xDl@xCz@tDx@pCxBtI~Hf[XpAjAlJl@xFZbN@rNGl[[`G_A|Jg@rDwBtLeBnHw@jDoFpW[tBSdBe@nEMzAMlBSlGIpYD`LGfVEtwDH||@Sv{@?dSRxIf@vJRfChChYdCnWPdCL|BF~BB~B?`CA~BI~B{Czr@u@~OGl@EvAWnEo@hOyCvq@O|BSzBaHdp@sJn_AgS`nBqFli@_UrwBe@|Dw@dFSbAkAjFgA~D{AxEwBlFwDtGu@dAkFrHsOnRkAvAeLdOqAnBoBvCeEdHaDhGuHzO}@nBcL`V{AxDiB`Gc@jBc@rBe@rCc@zD]fDE`AQlEChE?xDJvD\\hFjA~KXhChFhf@~@~HxGpm@tGnl@XxBv@xHn@fI^|F`@bLF|DJjJCt}ACdr@Alt@Cx{@@lUEdy@Aje@EzPGpB]fHM|EEdEBjGH~Df@vLDvC?ru@A~{A@lVArS?tkBCva@CrmB@p]GhmBErsFOfH_@lGk@xFIf@}@xFw@|DwAjFuJ`\\qPdk@cBnFoEnOkDhLqOxh@qEjOuAfFy@pD]dBy@rFc@rDOfBOlBObDG~AGjD@jx@RhcD@ff@C|g@L~eBJvzBAfh@@bh@FpSHlFHp\\FfLJv`@~@||CFnb@?nLFjp@\\zvCOvm@Q|jA^lHr@|Er@|Cx@jChAnCbDxFry@zmA~DrGxBpF~AjGn@nFVnF@nEGfyCAd\\AlB?fNDfDF~BFfAXbDLtBJrB~Dji@xBfZNrB|B|Zp@tJf@fDd@pBh@nBbApCx@pBnAvBxA|B|AjBp^rd@~IvKxA`Cn@lAnAbDnAzEt@|EVrCL`DFnCB~P?fD@f`@AfB@fPOjNI|FAlD@jTApWLjsA@bHBzPA~K@tCCdL?`JD|i@H`g@?jBCj_@Bvr@@`QFfJBbCDnK@dN@bR?dCCfRArDGrBOnCi@lGmA|REhA?vB?zJ?bC?r\\?xBP|RBjAn@xS~@jXFbCR~FJrDD~T?dC?`O@pCBvMAdR??i@rD_@~@[h@??iDfEuBvCc@fAETM`AA^@n@H|@Jd@L\\\\l@jB`C\\XZNXH^FX@ZCTE\\Kh@YNQT]z@}A^e@TORKn@Q`@CjABpI~@~Fr@tAJrGBdEAnEAfCRNBjAP|Ad@z@ZhB`ArAz@rO~Jz@t@h@f@`@f@x@lAn@pA^`AV~@XlATzAHbAFtC?zO@bE?vNHhCJrAZtBd@vBlArDx@bClBfF|DtK`EhLbFfNr@dB|BpEfCpErBjC~ApB|AbBlBfBzAnAzFzEbNbLtC~B|JhIvBdBvDfDjH|Ff[fW|InHfIrG`CjBhA~@rPhNxAnAtPdNp\\vXlO`M`JrHd]~X|BhBbGrEdGdD|EjCdEtBdStKrHbEdBx@jDxAfEzAhBh@hEdAdFbAlGr@fFp@ho@fIhh@tGziBpUpZvD|SlCx@Fr@BlGAbb@BvU@zQ?dVBzp@D|g@?jO?dg@BzLFhC?|H[lOs@pBW~Bc@rBm@jBw@nDqBhB{AhHwGbA}@pLiLf[aZ|[mZzTgTbD{CfT}RbF_FpDgDxG}Fl@e@xEqDvBgBxB_B|MgKjLwIbLyItQmNnC{BzL_JfEeDrB{AlWgSpFcE`W{RdDoCfBgBna@i`@xeAycAlQqPnBgBtByApCiBxEcC~CoApDkAhEgAdCe@vBYpDYhCMlAEr\\IzMCvr@?jMB~fA?baCJhc@B|DFfh@FfBB`v@TvhAVbKCfTo@t`@?zJ?~gABl`A?vb@Cj}ABf~A?~_@?bC@dMAz]B`LG`J[hE]dTkBhGa@~LKzNB|GAjSAr[Fz^BzC?t`@Clp@?hQDxC@`IChEMzBQrCYbBYxXaFjjIm{AdlAmTfGqA|C}@r[cLhc@qOjVsIra@uNhnBoq@dHeCdNyEt_Ak\\di@aRjAe@nGuCbDsBnDgCxPmNlDmC`BaAfAk@lCkAdBk@~Bo@jDo@tBS`BIrBEzC@x@BrALbD\\tM`BxCX|I`AdBLjDJlRDxp@?p}@FvIBrZ?pLDlJDzCPxK~@hDPz`@DpGArh@@lC@nCC`ACjBOxC_@r@M`EgAxAc@hDwAnEoBzVaLvt@e\\jr@e[`j@iV`CcAfBk@|C}@rBc@hDi@dC]nCUjBI~CEvJ@jVCvfAFzOElHF|m@BbSEzP@tJAlQDzS?xl@@`NC~K@pR?t]HtG?`\\Hjd@Ifq@C~_A?xBBvDGnCKtAIzDa@rASzAYzA]~DeAfBm@~@_@~DeBlDmBv@e@re@_^fe@w]|JwH|m@wd@pi@ka@fKsH|MgKfSgOfAu@|A}@bB}@fBy@bCeAdDkA`HyBlGmBtHiC|L{DjNsE|g@kPzQeGbJqCtNwE`MgEnxB_s@t|@gYbMaEpr@{TdwAwd@lEkAvDg@vc@[zg@g@|ZWrJGtJDtG\\lARrE`AlExAdLtGlCxBnDzEfFdG`GbHz^hd@nKzM`F|FpHrJdTvWbClClEpF`L|LpB|BjHjIxFxFlGlHzHtI~RvU`IxJzAdB`BxAnAz@hAn@`An@zAp@fBn@zBn@lCZ`BJjBDbF@bUSbJAlB@pMMpDBpIb@pRr@nDVnI`@dG@hDItDUpKu@nGk@nKu@nKw@l]uAdO]jk@mA``@Sph@Q`LKvAAlO?vH`@|HdBbIlDfAr@jB~A~DdDjOtTvAnBlS|XxE~GbOhTtE|GnAtBhDpGjD`HlKnOrMjRrIrLfUp\\jHtKzChFtd@feA~_@l}@fSle@ls@haBhHzP|FrMbJvOzEbH~D|FzJhNxDlEtBxAdAf@`GfBtEn@p@@lIJpa@?vERpCf@bChAvDlBfCnCdAfAtCdDpGnH`EjFrGlHhExC|AfAdHvCvDlAjBZ`F`@pZNbdA?pWD|e@E`nCLbt@@pGBrZAt_@Qj^@hfAFtsBQbZKxU?fLBbKFne@@xU?|]Frk@Fp_ACvtAAx^AtKCxBCxAAdEEnTDbHFnO?xkAGpLKvFe@vHkBzEwBvFuDvDoDhJaL|HmJlOyRfByBl@u@tBgCdBsApDsAhCUxKB~RDdYGfDCpM?xCAnEB|JEnEOfCWtBY|Cq@pA_@bBm@dIiDnBu@tDkAvBe@`B]`Fq@~DYbAE|ACp{AQvz@?t]?v^Ely@Gh`BCtP?vA@fBDbADfAN~Bd@dB`@jCr@bFlB~B`AbNtFlCbAfA^vDbAlCl@fBVzCXvCTbBHfBDjP@~H?zR?xAGhBMvAQlAQfAWjA[zAg@jAe@dEuBrAq@v@a@nBu@jCs@~Bc@nFa@bMAxLAbCHvCPbKlA~BRnFTlA@~BBzDFvF@|JJfDEzCGvAQnBYfASdAW~Ai@zJwBnEeApD}@tLqC|Bi@hDy@t@QzCe@zD_@rAItEKlB?|C@fOG~a@GzB?hSGpYCpSIbB?xBD|BNxBXjCb@dDZlCLvC@j]GbD?z^IpBIjBSf@IvAMvEw@`AKnBIp\\MpDA`ME`GIxJCjBBrGCxGIpX]bEC|B@bE^fBR|B^tCz@v@T`Bn@hAh@vC~A~@n@`CjBjFpEv\\xXnCvB`DzCnBtAbB~@rAh@zA\\vC^t@Dj@@xBAnBQ`MuAnEe@d[iD~AOlCYxCi@rCm@vBk@t@WlG_C??dAU~Am@`A]xBeAzIyEl@YvB}@t@U`AO|AI??l@RNRLXAvADzA??FrB@|@Cv@UzCIrB??u@pHc@rFIlECvB@fE@p`@?zD@\\BzJBnbAAl^DvCFdBLdBr@tEd@lBl@jBt@fB|@`B|ElI|JlQvPd[tIjPrWvg@zDdJjD~HtCjHjDxGbE~G~BpDfB~ChA~AbOdVlCbFvHrNhKbRdDzGfBhE`FbMlBxEdDxHrAnCz@~Ab@x@nB~CbBrCdF`IzEtHbJnMrGfKxEdJhEbIbElIzAnDzE~NnBnF~DdIpEhIbFhI??|\\dm@fEfIxBtDbHzMvQv]nAfC`DzFpBxDpDdHzJnRtCtFjBzD|BlE~J`Rt\\no@jA~B^~@Xj@fPrVjDhF~BrDbDtDxBzB`CpB~CtBfE`CvFvB~RtGrMlEtDjBlDdCrCdCfCtCdCrDxBzD`CxGrAjEt@xBzC~IjAbFz@vG^vE\\pQZxZFz@DhAt@tGvBlLjArG^hBj@|Bf@fBpBjFl@lAnYfj@~IzPrEhJbBxChHhNdc@zy@|Ttb@`GlLtKvS~BrExOzYhGpLbGtLbCdEp@rAtAlCdu@fwAdN`X~h@hdAxPv[~Sr`@|g@vaArF~Jh\\xn@vIlPjUlc@`BlCbArAt@|@~@`AhAbAjOjLzWfSfOfLvBpAZNXCtCvAlAr@|AfApDrCtDtC`FpDfCjBnCzBlCpCJ`@j@n@nArAnBbBda@xZxc@x\\zi@`b@r[`VvDzBdD|A`NtFnHpCfHtC`KbE|X~KzFxBbm@bVvj@xTxd@zQd^tNjH~CnBv@lBb@`EdBfQ`HdFrBbJnDjChA~ZbMvTvIrb@tPzu@nZryAjl@nyAbl@dzAll@vIlDh^tNdp@rWlTvIfS|HbFrBfO`G`LrEdK`ElVtJnW`KvJbEfK~Df|@t]~n@zVrTtItb@|PdA\\bBj@|@XnB`@t@LpCT~DHjIJvCFpEBvAA`DGl@Eb@KdGClE@tEB|BCpA@rBOxANhB?bB?tD@V?f@?L?b@?D?X?t@?`C?rBAjA?b@AZ?T@R@ZFFB^J\\`@VD~AfAf@P^Fb@BnC?|ECvEAhE?nE?v@Fj@Jz@Xj@ZXTVV`BvBb@D??\\p@`BlBbAdAzUbXdBfBrFdGnArB~CnF|CrF`DlFlAlBrAxAnDtGDd@~EfIhC`E|AxBtAxAvAlAfC~AjCxAnAv@`@\\|B|BfArAzRfWtCzDpE|Fn@|@fAlBf@bAl@xA`ExK\\x@n@rAvAhCxFxHxJfMvEjGvn@by@fVd[jMtPhQbUnLrOrDpEzElGdRnVjG`IxBrChs@x~@dc@|j@v{@~hAvI`LbLjNjA|Aja@rh@~q@~|@hRlV`q@b|@jF`Hp[pa@jb@hj@hQxTtG|HxE~E~AdBXH~ApBV\\hDbEV\\X\\vCtDdKxMxBtCNb@tBvCxG`KlAlBlHjKn{@lhArUrZjaBhvBzOrSdg@ro@jCpDdm@`w@|NhR~AjBjVj[|G`JnHfJj]~c@dP|StHrJt~@dlAbC|CXXnAhAbBhAxBfArAh@T@zAr@zAx@`Ax@Z^l@x@V^j@nAZbA^|APfBFfB?nHBrBFtAHt@RnAVdA\\dAj@hAT\\fArAZXhAv@rAp@xFdCvBbAjAx@x@t@j@n@~@lAva@bi@fBhC~C|Et@jAbCvDzF`J~EtGxEfGvCrDtCpDjA|AfAvApCrDvEfGz@fAfCbD`LxNnEzFjGbInPdTrIzKnJzLHXhBdCbEtGvBxC`ItJpDjEdRlUfS`Vzy@rbAbNnPfPlRrp@|w@|Yx]rMjOzVvZ`j@dp@fDpC|CpBfEbBrFrApBd@vCPjCJrmAI~OH`[@bm@IhMBrp@CpKGrAApJBxg@GvRGn]Lrc@Kju@A`VEhUDrl@CZAvTL`V@xd@KvF?bSDfBBvbAGn@?jE?`E?fj@Cx`@AfB?xCLpAPpA\\`Bh@`GhBfCp@`Ch@t@Pf@CvJpClEtApEvA`EnAPFnDhA`@LnEtAfAZzAXl@FfAHfA@zIChGAdGAhGGpEA??ArF@xHAzD?~CCxACbAKxBCn@e@xFq@vFcAlFiAxFy@~DuEpTcBtHoGt[If@_FtVe@jBg@|D}@bE_A`IKfDEbFFv`AHxb@A~^FjaAStw@?tJHlf@DhjBChkBE~iB?djB@~X?hjCAhN?f`@Hnw@Czr@GjpAFvL?fg@B~HCvPYhLYjOU|IA~EBbyBBrnBBnLIjJ[jK_Ax]oAve@_F||AgBlp@VtXz@fv@v@ru@r@vd@n@xn@JhO?~_ACrp@Fvu@Fp\\Bv~AAdc@AlU@fEDd[BvMJ~G`@~SR|I`@dSj@~YRzTNhQJfLRlUHdJRnUH`IJnJDdBJpBH|@ZjCPbAf@`Cx@rCdAlCbAtBfAjBv@dAnAzA`A`A`T~Q~FbFjHrGrJnItFxE|EjEfDrCdA~@f@b@nAfAtN~LjEvDjPrNfB|AVPR?\\XpAhAfHjGlC|BzFbF|DhD|BnB`Ax@f@d@Z`@n@~@r@~A`@lANt@ATJ~@H`B?vB@xF?z@D\\@xCAvF?rF?xF?vFAxF?vF?vF?vF?tFA`F?RAdA@~K@vFAjF?nF@j^?pCAjBAjEBd\\AvH?hN?rU?rT?rF?|Q?f@?zGKl@?pG?pA?dBCjR?LPlAE~nA@bECfc@?nC?fLA|r@?^?V?|D??K\\E^A~^E~{@M~xC?rFEti@?jICxb@???p@Cbe@?lb@Era@AnM@hSIna@Xn}EA`KB`vARv}BAdZFxEPdFVzCZdDd@dDz@dFx@xDhA|DzAnEdBbFpEjMhFtO`Kn[nDlKlNda@la@fkAfSpk@`dAtwCzm@jfBdn@dgBlKnZpg@rxA|Wtu@dCjHfDvJtNda@dA~CpJjXb]xaA`_@bfA|]rbAz_@vgAlDnJjD`Kn@vBrIzZbc@t}Ad@dBbb@d{A`C`Jdo@b}Bxj@tqBjn@|zBfMfd@r\\nlAfB`HxBrJbiA|xFdc@|xBvN|s@hAbFpBtHdB`Gb@dBjQho@jJz[rHbXnAzElAfEhg@lhBt@dE~@dGf@jETvDRhF^~PdAtb@f@zT`DbtAb@fQfDpwAnAlf@hClfAd@lTvDv~Ar@jZVtFp@pM|HbyAbPt|CvDxr@nAhVlB|]^jHHnCFtDCdDOpGYlFwTzbC_BjRsMjxAcHpv@{OpfBuJffAg@pGQxFC|FFtETjG^vEd@lEt@rEj@tChAtEhArDbAnC|qAx~CxKpWzQdc@t@fBnB`EpBpDhT`\\x{BbiDlRjYhF~H|s@~fAdQxWb\\rf@pOtUfSnZjF~H`Ynb@lMnRtJbOlEtG~I~M~AdCh[be@fDrFhAjBbC`FjCfHd@xAxBlGtDvKnGfRdBdF`K~YbGbQrDrKlFtOvI`WpCdIlRlj@tGlRtM|_@zIfWlBxFxAdEdA|Cr@xBZfARx@RnARhBDx@FvABrA?`BAlC?j@?\\CnG?D?J@fC@`C?bCA|BAdC?rBFR??P?vC@b@@h@?lA@~@?\\?~@?jFB`JQ`GMpBK|AQbAOjB]`B_@zAc@fAc@dAc@h@[bAk@P[\\Uf@[lAeA~BcChHqIn@k@pA{@vAo@x@WvPuDdYgGjGuAnCk@pAY~Bg@JCVDxEu@dD]tAGjAB`AFx@LNBxAd@xAt@ZRh@XPJP?zCfB~HxEpGvDxJ~FnC~AfAp@lDtBjAp@h@ZxHnE`DlB~D`Cr@b@JFj@\\d@VdBdAh@ZdJpF~BvAbB`AhHjEz@f@B@lAt@hAp@xIhFhWlO~@h@HDLJh@ZJFjYzPlBjA`DlBtGxDfL~G`CtAjp@n`@`@TlVzNfAn@HPrBnAdJfGj^jTt@d@fCpAzEtB~s@nYrEhBf_Ad_@~\\`Ntn@zVhEbBxB|@dJpDnKpDpBv@bNjFxEnBpVtJlTzIzw@f[nIhDnIfDrd@vQzIpDzYhLfUdJpLzErLrEnOfG|KrEnBz@pNpF`ZpLfE~Aht@vYtKlEhVlJdI~CjElBzBz@zOxG~Bt@`DpA|CvAzDxBbDzBjA|@pAfAlAjAnArAlArAjAzAvPhUbClDlJ`MdA~A`FtG|P|UjOnSbB`ChDjEn^`g@nFjHlL~OhB`ClAhBvGvIxC`EtB`Dp[tb@pC~Dz@pA~BbErAxCp@hB~@pCj@nBbArE^pB^hCNjAr@lIbAhMHbAv@tIRhCLhA`@xB\\xAt@fCDJn@dBTf@hAzBt@jAv@bArA~An@l@rBfBnFjDZTjBhAbFbDvAx@hKxGjAr@rLlH`C~A~PvKdOhJfLlHtBnA|BzAfCxArCfBrQfLjJ`GxBxApIdF`HlEdFdDlDtBzB|AhAn@`_@nUvEzC~MpItBnAlCdBtD`CfNxIvSlMlJbGzCnBpC`B`BjAtBnAnFlDvCbBnHtExE`DxAv@~EbDtEnC|GhExCnB~MpIvKzGxCpBhBdAtAbAP?zK`HrK`HlM`IhCdBfCxApBrAtSlMdM`IvNzIdC`Bj\\tS|@l@lN~ItN|I^VjCbBfNrIlAz@lBnAjNtIrEzCrHvEx@l@nS~L`JzFD@hMdIlC~AhG|D`W|OpRxL~GdEfIlF|IlFvClBdIbFhIdF`ElCdKpGpLhH`HnEhDrBlInF`DnBpm@p_@nInFvFrDbV`O`NpIvRvLdAv@~AtAhAhA~@jAjA`BfAnBjAfCl@`Bj@pBt@lDfIzc@zAdJ~F|[|^vqBnHva@L^DXrAnH`ArF~Fp[bs@f|D~DrOxa@hpAl_@fiAlGnUlDtO`[vhCrE|b@pFxd@hp@x}FHl@h@tDh@tC^fB^vAv@nCl@dBl@xAlBrE|P|[tTnb@`J~PzB~DxA|B~ArBf@p@"            
         }
         */
        $distance = $mqResult->{"route"}->{"distance"};
        if($distance > 0){
            $newLeg = new TripLeg();
            $newLeg->id =$lastid+10;
            $newLeg->distance = $distance;
            $newLeg->startLatLong = $lastLatLong;
            $newLeg->endLatLong = $newLatLong;
            $newLeg->MQencodedPath = $mqResult->{"route"}->{"shape"}->{"shapePoints"};

            //echo json_encode($newLeg);

            //Push the leg to the end of the $tripPaths->{$trip}
            array_push($tripPaths->{$trip},$newLeg);
            //var_dump($tripPaths);

            if($newTripsRoute = fopen($jsonRouteFilePath,"w")){
                fwrite($newTripsRoute, json_encode($tripPaths));
                fclose($newTripsRoute);
                $result = TRUE;
            }else{
                //error using fopen.
                $error_msg = "Could not open file.";
                $result = FALSE;
            }
        }else{
            //No distance between provided point and last point.  Likely the same point was provided.
            $error_msg = "No distance travelled";
            $result = FALSE;
        }
    }else{
        $error_msg = "No MapQuest result given. Could not add Leg to trip.";
        $result = FALSE;
    }
    if(!empty($error_msg)){echo $error_msg;}
    return $result;
}

function generateFullPath() {
    //Looks at all the coordinates in the database and creates a JSON path file.
    include_once('../common/common_functions.php');
    $jsonRouteFilePath = "cricketTraveledPath.json";
    $error_msg = "";
    $result = false;
    $travelObj = new Travel();
    $tdh_db = "CLEARDB_URL_TDH_SCRIPTS";
    
    //Get the points from the database sorted by tripname, then date
    try{
        if($db = connect_db($tdh_db)){
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            $query_sel = $db->query("SELECT * from gps_readings order by `tripname`, `time` ASC");
            $previousLatLong = [];
            $previousTripName = "";
            $tripLeg_id = 0;
            if($query_sel->rowCount() > 0){
                foreach ($query_sel as $row){
                    $currentLatLong = [ $row['lat'], $row['long'] ];
                    $time = $row['time'];
                    if(!empty($previousLatLong)){
                        //get the mapquest info
                        $mqString = getMQroute($previousLatLong, $currentLatLong);
                        if($mqString){
                            $mqResult = json_decode($mqString);
                            if( $row['tripname'] !== $previousTripName){
                                $newTrip = new Trip();
                                $newTrip->name = ($row['tripname']);
                                $travelObj->add_trip($newTrip);
                                $previousTripName = $row['tripname'];
                            }
                            //Get the latest trip object from the travelObj
                            $currentTrip = end($travelObj->trips);

                            //If the points not the same make a new leg
                            $distance = $mqResult->{"route"}->{"distance"};
                            if($distance > 0){
                                $tripLeg_id += 10;
                                $newLeg = new TripLeg();
                                $newLeg->id = $tripLeg_id;
                                $newLeg->distance = $distance;
                                $newLeg->startLatLong = $previousLatLong;
                                $newLeg->endLatLong = $currentLatLong;
                                $newLeg->startTime = $time;
                                $newLeg->MQencodedPath = $mqResult->{"route"}->{"shape"}->{"shapePoints"};

                                //add the leg to the current trip
                                $currentTrip->add_leg($newLeg);
                            }
                        }else{ 
                            //Map Quest result not returned
                            $error_msg = "Map Quest result not returned";
                            $result = false;
                            
                        }
                    }
                    $previousLatLong = $currentLatLong;
                }
                //If none of the leg creations failed,
                //Turn the object into a json string and update the file.
                if($result){
                    if($newTripsRoute = fopen($jsonRouteFilePath,"w")){
                        fwrite($newTripsRoute, json_encode($travelObj));
                        fclose($newTripsRoute);
                        $result = true;
                    }else{
                        //error using fopen.
                        $error_msg = "Could not open file.";
                        $result = false;
                    }
                }
            }
        }else{ //handle error
            $error_msg = "Could not connect to database";
            $result = false;
        }
    }catch(PDOException $ex) {
        $error_msg = "CODE 120: Could not connect to mySQL DB<br />"; //user friendly message
        $error_msg .= $ex->getMessage();
        $result = false;        
    }
    echo $result ? "Success! New file created." : $error_msg;
    return $result;
}