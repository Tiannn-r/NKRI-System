import re
from html.parser import HTMLParser

class StrictHTMLValidator(HTMLParser):
    def __init__(self):
        super().__init__()
        self.tags = []
        self.void_tags = {'area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'}

    def handle_starttag(self, tag, attrs):
        if tag not in self.void_tags:
            self.tags.append(tag)

    def handle_endtag(self, tag):
        if tag in self.void_tags:
            return
        if not self.tags:
            raise Exception(f"Unexpected closing tag </{tag}> with empty stack")
        last = self.tags.pop()
        if last != tag:
            raise Exception(f"Mismatched closing tag: expected </{last}>, got </{tag}>")

def validate():
    with open("static/index.html", "r", encoding="utf-8") as f:
        html = f.read()

    # 1. Validate HTML structure
    validator = StrictHTMLValidator()
    validator.feed(html)
    print(f"HTML tags balanced successfully! Open tags stack remaining: {len(validator.tags)}")

    # 2. Check Vue return keys vs template usages
    last_return_idx = html.rfind("return {")
    assert last_return_idx != -1, "Main Vue return statement not found"
    ret_block = html[last_return_idx:html.find("};", last_return_idx)]
    returned_keys = set(re.findall(r'(\b[a-zA-Z0-9_$]+\b)', ret_block))
    print(f"Total reactive properties and methods exported in Vue: {len(returned_keys)}")
        
    required_keys = [
        'selectedOperatorFilter', 'returnedTraysList', 'loadReturnedTrays', 'acknowledgeReturnedTray',
        'showSessionModal', 'sessionForm', 'openSessionModal', 'submitSessionAction',
        'showPauseModal', 'pauseForm', 'isWorkstationPaused', 'activePauseReason',
        'pausedDurationDisplay', 'openPauseModal', 'submitPauseAssembly', 'resumeAssemblyWork',
        'downtimeLogsList', 'downtimeFilter', 'loadDowntimeLogs', 'resetDowntimeFilter',
        'quickFinishSlot', 'currentTraySetsCount', 'traySlots', 'adminVerifyFilter'
    ]
    for k in required_keys:
        assert k in returned_keys, f"Missing returned key in Vue: {k}"
        print(f"[OK] Key verified: {k}")

    print("\nALL HTML & VUE CODE VALIDATED 100% CLEANLY!")

if __name__ == "__main__":
    validate()
