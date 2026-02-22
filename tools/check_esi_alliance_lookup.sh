#!/usr/bin/env bash
set -euo pipefail

NAME="${1:-Goonswarm Federation}"
DATASOURCE="${2:-tranquility}"

USER_AGENT="pathfinder-alliance-check/1.0"

IDS_RESPONSE=$(curl -fsS -X POST "https://esi.evetech.net/latest/universe/ids/?datasource=${DATASOURCE}" \
  -H "User-Agent: ${USER_AGENT}" \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  --data "[\"${NAME}\"]")

ALLIANCE_ID=$(python - <<'PY' "$IDS_RESPONSE"
import json, sys
payload=json.loads(sys.argv[1])
alliances=payload.get('alliances') or []
print(alliances[0]['id'] if alliances else '')
PY
)

if [[ -z "${ALLIANCE_ID}" ]]; then
  echo "No alliance id found for '${NAME}' on datasource '${DATASOURCE}'."
  exit 1
fi

ALLIANCE_RESPONSE=$(curl -fsS "https://esi.evetech.net/latest/alliances/${ALLIANCE_ID}/?datasource=${DATASOURCE}" \
  -H "User-Agent: ${USER_AGENT}" \
  -H 'Accept: application/json')
ALLIANCE_NAME=$(python - <<'PY' "$ALLIANCE_RESPONSE"
import json, sys
payload=json.loads(sys.argv[1])
print(payload.get('name',''))
PY
)

echo "name='${NAME}' datasource='${DATASOURCE}' alliance_id=${ALLIANCE_ID} resolved_name='${ALLIANCE_NAME}'"
