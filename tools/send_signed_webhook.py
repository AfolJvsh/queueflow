#!/usr/bin/env python3
import argparse, hashlib, hmac, json, time, urllib.request
p=argparse.ArgumentParser(); p.add_argument('url'); p.add_argument('secret'); p.add_argument('--delivery',default=None); p.add_argument('--order-id',default='ord_demo_001'); a=p.parse_args()
payload=json.dumps({'order_id':a.order_id,'source':'signed-demo'},separators=(',',':')).encode(); ts=str(int(time.time())); sig=hmac.new(a.secret.encode(),ts.encode()+b'.'+payload,hashlib.sha256).hexdigest(); delivery=a.delivery or hashlib.sha256(payload).hexdigest()
req=urllib.request.Request(a.url,data=payload,method='POST',headers={'Content-Type':'application/json','X-QueueFlow-Timestamp':ts,'X-QueueFlow-Signature':sig,'X-QueueFlow-Delivery':delivery})
with urllib.request.urlopen(req) as r: print(r.read().decode())
