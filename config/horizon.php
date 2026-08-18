<?php
use Illuminate\Support\Str;
return [
    'name'=>env('HORIZON_NAME'),'domain'=>env('HORIZON_DOMAIN'),'path'=>env('HORIZON_PATH','horizon'),'use'=>'default',
    'prefix'=>env('HORIZON_PREFIX',Str::slug(env('APP_NAME','queueflow'),'_').'_horizon:'),'middleware'=>['web'],
    'waits'=>['redis:workflows-high'=>30,'redis:workflows'=>60,'redis:workflows-low'=>180,'redis:default'=>60],
    'trim'=>['recent'=>60,'pending'=>60,'completed'=>60,'recent_failed'=>10080,'failed'=>10080,'monitored'=>10080],
    'silenced'=>[],'silenced_tags'=>[],'metrics'=>['trim_snapshots'=>['job'=>24,'queue'=>24]],'fast_termination'=>false,'memory_limit'=>128,
    'defaults'=>[
        'high'=>['connection'=>'redis','queue'=>['workflows-high'],'balance'=>'auto','autoScalingStrategy'=>'time','maxProcesses'=>1,'maxTime'=>0,'maxJobs'=>0,'memory'=>192,'tries'=>1,'timeout'=>180,'nice'=>0],
        'standard'=>['connection'=>'redis','queue'=>['workflows','default'],'balance'=>'auto','autoScalingStrategy'=>'time','maxProcesses'=>1,'maxTime'=>0,'maxJobs'=>0,'memory'=>192,'tries'=>1,'timeout'=>180,'nice'=>0],
        'low'=>['connection'=>'redis','queue'=>['workflows-low'],'balance'=>'auto','autoScalingStrategy'=>'time','maxProcesses'=>1,'maxTime'=>0,'maxJobs'=>0,'memory'=>160,'tries'=>1,'timeout'=>180,'nice'=>5],
    ],
    'environments'=>[
        'production'=>['high'=>['maxProcesses'=>5,'balanceMaxShift'=>2,'balanceCooldown'=>2],'standard'=>['maxProcesses'=>4,'balanceMaxShift'=>1,'balanceCooldown'=>3],'low'=>['maxProcesses'=>2,'balanceMaxShift'=>1,'balanceCooldown'=>5]],
        'local'=>['high'=>['maxProcesses'=>2],'standard'=>['maxProcesses'=>2],'low'=>['maxProcesses'=>1]],
    ],
    'watch'=>['app','bootstrap','config/**/*.php','database/**/*.php','public/**/*.php','resources/**/*.php','routes','composer.lock','composer.json','.env'],
];
