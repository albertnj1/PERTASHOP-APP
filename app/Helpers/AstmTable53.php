<?php

namespace App\Helpers;

class AstmTable53
{
    // Grid values extracted from ASTM Table 53 images
    // Format: density -> [temp -> value]
    private static $grid = [
        0.700 => [0.0 => 0.6869, 5.0 => 0.6913, 10.0 => 0.6957, 15.0 => 0.7000, 20.0 => 0.7043, 25.0 => 0.7085, 30.0 => 0.7127, 35.0 => 0.7168, 40.0 => 0.7209, 45.0 => 0.7249, 50.0 => 0.7289],
        0.710 => [0.0 => 0.6971, 5.0 => 0.7014, 10.0 => 0.7057, 15.0 => 0.7100, 20.0 => 0.7142, 25.0 => 0.7184, 30.0 => 0.7225, 35.0 => 0.7265, 40.0 => 0.7305, 45.0 => 0.7345, 50.0 => 0.7384],
        0.720 => [0.0 => 0.7073, 5.0 => 0.7116, 10.0 => 0.7158, 15.0 => 0.7200, 20.0 => 0.7241, 25.0 => 0.7282, 30.0 => 0.7323, 35.0 => 0.7362, 40.0 => 0.7402, 45.0 => 0.7441, 50.0 => 0.7479],
        0.730 => [0.0 => 0.7175, 5.0 => 0.7217, 10.0 => 0.7259, 15.0 => 0.7300, 20.0 => 0.7341, 25.0 => 0.7381, 30.0 => 0.7421, 35.0 => 0.7460, 40.0 => 0.7499, 45.0 => 0.7536, 50.0 => 0.7573],
        0.740 => [0.0 => 0.7277, 5.0 => 0.7319, 10.0 => 0.7360, 15.0 => 0.7400, 20.0 => 0.7440, 25.0 => 0.7480, 30.0 => 0.7518, 35.0 => 0.7557, 40.0 => 0.7594, 45.0 => 0.7631, 50.0 => 0.7667],
        0.750 => [0.0 => 0.7379, 5.0 => 0.7420, 10.0 => 0.7460, 15.0 => 0.7500, 20.0 => 0.7539, 25.0 => 0.7578, 30.0 => 0.7616, 35.0 => 0.7653, 40.0 => 0.7689, 45.0 => 0.7728, 50.0 => 0.7767],
        0.760 => [0.0 => 0.7471, 5.0 => 0.7511, 10.0 => 0.7551, 15.0 => 0.7590, 20.0 => 0.7628, 25.0 => 0.7666, 30.0 => 0.7708, 35.0 => 0.7748, 40.0 => 0.7787, 45.0 => 0.7825, 50.0 => 0.7863]
    ];

    /**
     * Get Density at 15C using Bilinear Interpolation
     * 
     * @param float $obsDens Observed Density
     * @param float $obsTemp Observed Temperature
     * @return float|null
     */
    public static function getDensity15C($obsDens, $obsTemp)
    {
        if ($obsDens < 0.700 || $obsDens > 0.760 || $obsTemp < 0 || $obsTemp > 50) {
            return null; // Out of bounds
        }

        $densKeys = array_keys(self::$grid);
        $tempKeys = array_keys(self::$grid[0.700]);

        $d1 = $d2 = 0;
        foreach ($densKeys as $k) {
            if ($k <= $obsDens) $d1 = $k;
        }
        foreach (array_reverse($densKeys) as $k) {
            if ($k >= $obsDens) $d2 = $k;
        }

        $t1 = $t2 = 0;
        foreach ($tempKeys as $k) {
            if ($k <= $obsTemp) $t1 = $k;
        }
        foreach (array_reverse($tempKeys) as $k) {
            if ($k >= $obsTemp) $t2 = $k;
        }

        if ($d1 == $d2 && $t1 == $t2) {
            return self::$grid[$d1][$t1];
        }

        if ($d1 == $d2) {
            $val_t1 = self::$grid[$d1][$t1];
            $val_t2 = self::$grid[$d1][$t2];
        } else {
            $fd = ($obsDens - $d1) / ($d2 - $d1);
            $val_t1 = self::$grid[$d1][$t1] + $fd * (self::$grid[$d2][$t1] - self::$grid[$d1][$t1]);
            $val_t2 = self::$grid[$d1][$t2] + $fd * (self::$grid[$d2][$t2] - self::$grid[$d1][$t2]);
        }

        if ($t1 == $t2) {
            return round($val_t1, 4);
        }

        $ft = ($obsTemp - $t1) / ($t2 - $t1);
        $finalVal = $val_t1 + $ft * ($val_t2 - $val_t1);

        return round($finalVal, 4);
    }
}
