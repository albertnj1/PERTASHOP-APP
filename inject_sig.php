<?php
$content = file_get_contents('resources/views/monthly_reports/show.blade.php');

$search = "    </div>\n    <!-- END BOTTOM SECTION -->\n\n</div>";
$replace = "    </div>\n    <!-- END BOTTOM SECTION -->\n    @include('monthly_reports.signature')\n</div>";
$content = str_replace($search, $replace, $content);

$search2 = "        </table>\n    </div>\n\n    <!-- PAGE 3: POSISI MODAL KERJA -->";
$replace2 = "        </table>\n        @include('monthly_reports.signature')\n    </div>\n\n    <!-- PAGE 3: POSISI MODAL KERJA -->";
$content = str_replace($search2, $replace2, $content);

$search3 = "        </table>\n    </div>\n\n    <!-- PAGE 4: REKAPITULASI -->";
$replace3 = "        </table>\n        @include('monthly_reports.signature')\n    </div>\n\n    <!-- PAGE 4: REKAPITULASI -->";
$content = str_replace($search3, $replace3, $content);

$search4 = "            </tbody>\n        </table>\n    </div>\n\n</div>";
$replace4 = "            </tbody>\n        </table>\n        @include('monthly_reports.signature')\n    </div>\n\n</div>";
$content = str_replace($search4, $replace4, $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Injected signatures!";
