"""
Cena livre: o Claude compõe cada animação do zero, camada por camada, com
quadros-chave e curvas no estilo GSAP — sem template. É o "HyperFrames" do
estúdio: em vez de HTML/CSS/GSAP num Chrome (que a hospedagem não aguenta),
uma descrição em dados que o Skia desenha. Dados, nunca código: o vídeo do
cliente (conteúdo não confiável) não consegue executar nada no servidor.

Formato completo (com exemplos): resources/video/motion.md
"""
import math
import random
import re

import skia

from base import (Elemento, _NUM, brilho_radial, cartao, clamp, cor, desfocado, escrever, imagem, largura, mix,
                  quebrar, rrect, tinta)

# --- Curvas (nomes do GSAP) ------------------------------------------------


def _power(k, modo):
    if modo == "in":
        return lambda p: p ** k
    if modo == "inout":
        return lambda p: (2 ** (k - 1)) * p ** k if p < 0.5 else 1 - ((-2 * p + 2) ** k) / 2
    return lambda p: 1 - (1 - p) ** k


def _expo(modo):
    def i(p):
        return 0.0 if p <= 0 else 2 ** (10 * p - 10)

    def o(p):
        return 1.0 if p >= 1 else 1 - 2 ** (-10 * p)

    if modo == "in":
        return i
    if modo == "inout":
        return lambda p: 0.0 if p <= 0 else 1.0 if p >= 1 else (2 ** (20 * p - 10) / 2 if p < 0.5 else (2 - 2 ** (-20 * p + 10)) / 2)
    return o


def _circ(modo):
    if modo == "in":
        return lambda p: 1 - math.sqrt(1 - p * p)
    if modo == "inout":
        return lambda p: (1 - math.sqrt(1 - (2 * p) ** 2)) / 2 if p < 0.5 else (math.sqrt(1 - (-2 * p + 2) ** 2) + 1) / 2
    return lambda p: math.sqrt(1 - (p - 1) ** 2)


def _sine(modo):
    if modo == "in":
        return lambda p: 1 - math.cos(p * math.pi / 2)
    if modo == "inout":
        return lambda p: -(math.cos(math.pi * p) - 1) / 2
    return lambda p: math.sin(p * math.pi / 2)


def _back(s, modo):
    c3 = s + 1
    if modo == "in":
        return lambda p: c3 * p ** 3 - s * p ** 2
    if modo == "inout":
        c2 = s * 1.525
        return lambda p: ((2 * p) ** 2 * ((c2 + 1) * 2 * p - c2)) / 2 if p < 0.5 else ((2 * p - 2) ** 2 * ((c2 + 1) * (p * 2 - 2) + c2) + 2) / 2
    return lambda p: 1 + c3 * (p - 1) ** 3 + s * (p - 1) ** 2


def _elastic(modo):
    def o(p):
        if p <= 0 or p >= 1:
            return clamp(p)
        return 2 ** (-10 * p) * math.sin((p * 10 - 0.75) * (2 * math.pi) / 3) + 1
    return o


_CURVAS = {}


def curva(nome):
    nome = (nome or "power2.out").lower().replace(" ", "")
    if nome in _CURVAS:
        return _CURVAS[nome]
    m = re.match(r"^([a-z]+)(\d)?(?:\.(in|out|inout))?(?:\(([\d.]+)\))?$", nome)
    f = _power(3, "out")
    if m:
        tipo, k, modo, arg = m.group(1), m.group(2), (m.group(3) or "out"), m.group(4)
        if tipo in ("none", "linear"):
            f = lambda p: p  # noqa: E731
        elif tipo == "power":
            f = _power(int(k or 1) + 1, modo)
        elif tipo in ("quad", "cubic", "quart", "quint"):
            f = _power({"quad": 2, "cubic": 3, "quart": 4, "quint": 5}[tipo], modo)
        elif tipo == "expo":
            f = _expo(modo)
        elif tipo == "circ":
            f = _circ(modo)
        elif tipo == "sine":
            f = _sine(modo)
        elif tipo == "back":
            f = _back(float(arg or 1.7), modo)
        elif tipo == "elastic":
            f = _elastic(modo)
    _CURVAS[nome] = f
    return f


# --- Caminho SVG (o skia-python não lê "d") --------------------------------


def caminho_svg(d):
    tok = re.findall(r"[MmLlHhVvCcSsQqTtAaZz]|" + _NUM.pattern, d or "")
    p = skia.Path()
    i, cmd, x, y, sx, sy, cx, cy = 0, "M", 0.0, 0.0, 0.0, 0.0, None, None

    def n():
        nonlocal i
        v = float(tok[i])
        i += 1
        return v

    while i < len(tok):
        if re.match(r"[A-Za-z]", tok[i]):
            cmd = tok[i]
            i += 1
            if cmd in "Zz":
                p.close()
                x, y = sx, sy
                continue
        rel = cmd.islower()
        c = cmd.upper()
        try:
            if c == "M":
                x, y = (x + n(), y + n()) if rel else (n(), n())
                p.moveTo(x, y)
                sx, sy = x, y
                cmd = "l" if rel else "L"
            elif c == "L":
                x, y = (x + n(), y + n()) if rel else (n(), n())
                p.lineTo(x, y)
            elif c == "H":
                x = x + n() if rel else n()
                p.lineTo(x, y)
            elif c == "V":
                y = y + n() if rel else n()
                p.lineTo(x, y)
            elif c == "C":
                a = [n() for _ in range(6)]
                if rel:
                    a = [a[0] + x, a[1] + y, a[2] + x, a[3] + y, a[4] + x, a[5] + y]
                p.cubicTo(*a)
                cx, cy, x, y = a[2], a[3], a[4], a[5]
            elif c == "S":
                a = [n() for _ in range(4)]
                if rel:
                    a = [a[0] + x, a[1] + y, a[2] + x, a[3] + y]
                r1 = (2 * x - cx, 2 * y - cy) if cx is not None else (x, y)
                p.cubicTo(r1[0], r1[1], *a)
                cx, cy, x, y = a[0], a[1], a[2], a[3]
            elif c == "Q":
                a = [n() for _ in range(4)]
                if rel:
                    a = [a[0] + x, a[1] + y, a[2] + x, a[3] + y]
                p.quadTo(*a)
                cx, cy, x, y = a[0], a[1], a[2], a[3]
            elif c == "T":
                a = [n(), n()]
                if rel:
                    a = [a[0] + x, a[1] + y]
                q = (2 * x - cx, 2 * y - cy) if cx is not None else (x, y)
                p.quadTo(q[0], q[1], *a)
                cx, cy, x, y = q[0], q[1], a[0], a[1]
            elif c == "A":
                rx, ry, rot, grande, horario, ex, ey = n(), n(), n(), n(), n(), n(), n()
                if rel:
                    ex, ey = ex + x, ey + y
                p.arcTo(rx, ry, rot, skia.Path.ArcSize.kLarge_ArcSize if grande else skia.Path.ArcSize.kSmall_ArcSize,
                        skia.PathDirection.kCW if horario else skia.PathDirection.kCCW, ex, ey)
                x, y = ex, ey
            else:
                i += 1
            if c not in "CSQT":
                cx = cy = None
        except (IndexError, ValueError):
            break
    return p


def suavizar(pontos, fechar=False):
    """Linha suave pelos pontos (Catmull-Rom → Bézier)."""
    p = skia.Path()
    if len(pontos) < 2:
        return p
    pts = [skia.Point(float(a), float(b)) for a, b in pontos]
    p.moveTo(pts[0])
    for k in range(len(pts) - 1):
        p0 = pts[k - 1] if k > 0 else pts[k]
        p1, p2 = pts[k], pts[k + 1]
        p3 = pts[k + 2] if k + 2 < len(pts) else p2
        c1 = skia.Point(p1.x() + (p2.x() - p0.x()) / 6, p1.y() + (p2.y() - p0.y()) / 6)
        c2 = skia.Point(p2.x() - (p3.x() - p1.x()) / 6, p2.y() - (p3.y() - p1.y()) / 6)
        p.cubicTo(c1, c2, p2)
    if fechar:
        p.close()
    return p


# --- Linha do tempo de uma propriedade --------------------------------------

ANIMAVEIS = ("x", "y", "escala", "escala_x", "escala_y", "rotacao", "opacidade", "progresso", "desfoque", "w", "h")

ENTRADAS = {
    "subir": ({"opacidade": 0, "dy": 70, "desfoque": 6}, "expo.out", 0.5),
    "descer": ({"opacidade": 0, "dy": -70, "desfoque": 6}, "expo.out", 0.5),
    "esquerda": ({"opacidade": 0, "dx": -90, "desfoque": 6}, "expo.out", 0.5),
    "direita": ({"opacidade": 0, "dx": 90, "desfoque": 6}, "expo.out", 0.5),
    "pop": ({"opacidade": 0, "escala": 0.55}, "back.out(1.8)", 0.45),
    "escala": ({"opacidade": 0, "escala": 0.7}, "expo.out", 0.5),
    "zoom": ({"opacidade": 0, "escala": 1.35, "desfoque": 12}, "expo.out", 0.55),
    "desfoque": ({"opacidade": 0, "desfoque": 22, "escala": 1.06}, "power2.out", 0.6),
    "girar": ({"opacidade": 0, "rotacao": -25, "escala": 0.6}, "back.out(1.6)", 0.55),
    "fade": ({"opacidade": 0}, "power2.out", 0.4),
    "mascara": ({"progresso": 0}, "expo.out", 0.6),
    "desenhar": ({"progresso": 0}, "power2.inOut", 0.8),
    "digitar": ({"progresso": 0}, "none", None),
    "letras": ({"progresso": 0}, "none", None),
}

SAIDAS = {
    "fade": ({"opacidade": 0}, "power3.in", 0.25),
    "cair": ({"opacidade": 0, "dy": 50, "desfoque": 6}, "power3.in", 0.25),
    "subir": ({"opacidade": 0, "dy": -60, "desfoque": 6}, "power3.in", 0.25),
    "encolher": ({"opacidade": 0, "escala": 0.8, "desfoque": 8}, "power3.in", 0.25),
    "esquerda": ({"opacidade": 0, "dx": -120}, "power4.in", 0.3),
    "direita": ({"opacidade": 0, "dx": 120}, "power4.in", 0.3),
    "zoom": ({"opacidade": 0, "escala": 1.4, "desfoque": 14}, "power3.in", 0.3),
    "apagar": ({"progresso": 0}, "power3.in", 0.3),
}


def _relativo(d, base):
    """dx/dy viram x/y absolutos (relativos à posição base da camada)."""
    out = {}
    for k, v in d.items():
        if k == "dx":
            out["x"] = base.get("x", 0) + float(v)
        elif k == "dy":
            out["y"] = base.get("y", 0) + float(v)
        elif k in ANIMAVEIS:
            out[k] = float(v)
    return out


class Linha:
    """Valor de cada propriedade animável no tempo t (segundos desde o início da cena)."""

    def __init__(self, camada, dur_cena, atraso=0.0):
        self.base = {k: float(camada[k]) for k in ANIMAVEIS if isinstance(camada.get(k), (int, float))}
        self.base.setdefault("x", 540.0)
        self.base.setdefault("y", 960.0)
        self.base.setdefault("opacidade", 1.0)
        self.base.setdefault("progresso", 1.0)
        kfs = []
        ent = camada.get("entrada")
        if ent:
            ent = {"tipo": ent} if isinstance(ent, str) else ent
            de, cv, dur = ENTRADAS.get(ent.get("tipo"), ENTRADAS["subir"])
            if dur is None:  # digitar/letras: duração pelo tamanho do texto
                n = len(str(camada.get("texto", ""))) or 10
                dur = n / (23.0 if ent.get("tipo") == "digitar" else 40.0)
            kfs.append({"em": float(ent.get("em", 0)) + atraso, "dur": float(ent.get("dur", dur)),
                        "de": _relativo(de, self.base), "curva": ent.get("curva", cv)})
        for k in camada.get("anim") or []:
            kfs.append({"em": float(k.get("em", 0)) + atraso, "dur": max(0.0, float(k.get("dur", 0.4))),
                        "de": _relativo(k.get("de") or {}, self.base), "para": _relativo(k.get("para") or {}, self.base),
                        "curva": k.get("curva", "power2.out")})
        sai = camada.get("saida", "fade")
        if sai and sai != "nenhuma":
            sai = {"tipo": sai} if isinstance(sai, str) else sai
            para, cv, dur = SAIDAS.get(sai.get("tipo"), SAIDAS["fade"])
            dur = float(sai.get("dur", dur))
            em = float(sai["em"]) + atraso if sai.get("em") is not None else dur_cena - dur
            kfs.append({"em": em, "dur": dur, "para": _relativo(para, self.base), "curva": sai.get("curva", cv)})
        self.kfs = sorted(kfs, key=lambda k: k["em"])
        self.entra = self.kfs[0]["em"] if ent and self.kfs else 0.0

    def valor(self, prop, t):
        base = self.base.get(prop, 1.0 if prop in ("escala", "escala_x", "escala_y") else 0.0)
        v = base
        visto = False
        for k in self.kfs:
            de, para = k.get("de") or {}, k.get("para") or {}
            if prop not in de and prop not in para:
                continue
            if t < k["em"]:
                if not visto and prop in de:
                    v = de[prop]
                break
            visto = True
            v0 = de.get(prop, v)
            v1 = para.get(prop, base if prop in de else v)
            p = clamp((t - k["em"]) / k["dur"]) if k["dur"] > 0 else 1.0
            v = mix(v0, v1, curva(k["curva"])(p))
        return v


# --- Cena -------------------------------------------------------------------


class CenaLivre(Elemento):
    """O componente 'cena': fundo, câmera virtual e camadas, tudo do plano."""

    entrada = 0.001
    saida = 0.001

    def preparar(self):
        d = self.d
        self.cheia = d.get("formato") == "tela_cheia"
        self.camadas = [self._montar(c, 0.0) for c in (d.get("camadas") or [])][:80]
        cam = d.get("camera") or {}
        self.cam_zoom = cam.get("zoom") or [1.0, 1.0]
        self.cam_dur = float(cam.get("dur", min(3.0, self.duracao)))
        self.cam_curva = cam.get("curva", "power2.out")
        self.cam_deriva = cam.get("deriva") or [0, 0]
        self.cam_foco = cam.get("foco") or [540, self.H / 2]
        self.cam_punches = cam.get("punches") or []
        self.rng = random.Random(hash(d.get("id", "c")) & 0xFFFF)

    def _montar(self, c, atraso):
        c = dict(c)
        c["_linha"] = Linha(c, self.duracao, atraso)
        if c.get("tipo") == "grupo":
            casc = float(c.get("cascata", 0))
            c["_filhos"] = [self._montar(f, atraso + i * casc) for i, f in enumerate(c.get("filhos") or [])][:40]
        if c.get("tipo") == "particulas":
            r = random.Random(len(c.get("id", "")) * 7919 + int(c.get("quantidade", 30)))
            area = c.get("area") or [0, 0, 1080, self.H]
            c["_p"] = [(r.uniform(area[0], area[0] + area[2]), r.uniform(area[1], area[1] + area[3]), r.uniform(0.4, 1.0), r.uniform(0, 6.28))
                       for _ in range(min(120, int(c.get("quantidade", 30))))]
        return c

    # cores: nomes da identidade ou hex
    def c(self, valor, alfa=1.0):
        mapa = {"destaque": self.est.destaque, "texto": self.est.texto, "primaria": self.est.primaria,
                "fundo": self.est.fundo_marca, "suave": self.est.suave, "cartao": self.est.cartao, "branco": "#FFFFFF", "preto": "#000000"}
        hexa = mapa.get(valor, valor) if isinstance(valor, str) else self.est.texto
        if not (isinstance(hexa, str) and re.match(r"^#[0-9a-fA-F]{6}$", hexa)):
            hexa = self.est.texto
        return cor(hexa, alfa)

    def hexa(self, valor):
        mapa = {"destaque": self.est.destaque, "texto": self.est.texto, "primaria": self.est.primaria, "fundo": self.est.fundo_marca, "suave": self.est.suave}
        h = mapa.get(valor, valor)
        return h if isinstance(h, str) and re.match(r"^#[0-9a-fA-F]{6}$", h) else self.est.texto

    def quadro(self, c, t):
        lt = t - self.inicio
        a_cena = 1.0
        if self.cheia:  # tela cheia: entra e sai com corte limpo de 2 quadros de opacidade
            a_cena = clamp(lt / 0.07) * clamp((self.duracao - lt) / 0.07)
            c.save()
            c.saveLayerAlpha(None, int(255 * a_cena))
            self._fundo(c, lt)
        else:
            c.save()
        # Câmera virtual: aproximação (ease-out) e deriva (linear) em camadas separadas.
        z = mix(float(self.cam_zoom[0]), float(self.cam_zoom[-1]), curva(self.cam_curva)(clamp(lt / max(0.01, self.cam_dur))))
        for pu in self.cam_punches:
            pe = float(pu.get("em", 0))
            du = float(pu.get("dur", 1.2))
            if pe <= lt <= pe + du:
                q = (lt - pe) / du
                pico = 0.3
                s = curva("expo.out")(q / pico) if q < pico else 1 - curva("power2.inOut")((q - pico) / (1 - pico))
                z *= 1 + float(pu.get("forca", 0.15)) * s
        dx = float(self.cam_deriva[0]) * lt / max(0.01, self.duracao)
        dy = float(self.cam_deriva[1]) * lt / max(0.01, self.duracao)
        fx, fy = float(self.cam_foco[0]), float(self.cam_foco[1])
        c.translate(fx + dx, fy + dy)
        c.scale(z, z)
        c.translate(-fx, -fy)
        for cam in self.camadas:
            self._camada(c, cam, lt)
        c.restore()
        if self.cheia:
            c.restore()

    def _fundo(self, c, lt):
        f = self.d.get("fundo") or {}
        f = {"cor": f} if isinstance(f, str) else f
        r = skia.Rect.MakeWH(self.W, self.H)
        cores = f.get("cores")
        p = skia.Paint(AntiAlias=True)
        if cores and len(cores) >= 2:
            cs = [self.c(x) for x in cores[:4]]
            if f.get("tipo") == "radial":
                p.setShader(skia.GradientShader.MakeRadial(skia.Point(self.W / 2, self.H * float(f.get("centro_y", 0.45))), self.H * 0.7, cs))
            else:
                ang = math.radians(float(f.get("angulo", 160)))
                cx, cy, rr = self.W / 2, self.H / 2, self.H / 2
                p.setShader(skia.GradientShader.MakeLinear([skia.Point(cx - math.cos(ang) * rr, cy - math.sin(ang) * rr), skia.Point(cx + math.cos(ang) * rr, cy + math.sin(ang) * rr)], cs))
        else:
            p.setColor(self.c(f.get("cor", "#0E0D0C")))
        c.drawRect(r, p)
        if f.get("grade"):  # grade sutil de fundo (tecnologia, dados)
            passo = float(f.get("grade_passo", 90))
            pg = tinta(self.c("texto", 0.045), "stroke", 1.5)
            desloca = (lt * float(f.get("grade_velocidade", 18))) % passo
            x = -passo + desloca
            while x < self.W + passo:
                c.drawLine(x, 0, x, self.H, pg)
                x += passo
            y = -passo + desloca
            while y < self.H + passo:
                c.drawLine(0, y, self.W, y, pg)
                y += passo
        if f.get("brilho"):
            b = f["brilho"]
            brilho_radial(c, float(b.get("x", 540)), float(b.get("y", self.H * 0.42)), float(b.get("raio", 620)), self.hexa(b.get("cor", "destaque")), float(b.get("forca", 0.35)))
        if f.get("vinheta", True):
            p = skia.Paint(AntiAlias=True)
            p.setShader(skia.GradientShader.MakeRadial(skia.Point(self.W / 2, self.H / 2), self.H * 0.75, [cor("#000000", 0), cor("#000000", 0.55)], [0.55, 1.0]))
            c.drawRect(r, p)

    # --- uma camada -------------------------------------------------------

    def _camada(self, c, cam, lt):
        ln = cam["_linha"]
        op = clamp(ln.valor("opacidade", lt))
        if op <= 0.003:
            return
        x, y = ln.valor("x", lt), ln.valor("y", lt)
        esc = ln.valor("escala", lt)
        ex, ey = esc * ln.valor("escala_x", lt), esc * ln.valor("escala_y", lt)
        rot = ln.valor("rotacao", lt)
        blur = max(0.0, ln.valor("desfoque", lt))
        prog = clamp(ln.valor("progresso", lt))
        c.save()
        c.translate(x, y)
        if rot:
            c.rotate(rot)
        if abs(ex - 1) > 1e-4 or abs(ey - 1) > 1e-4:
            c.scale(ex, ey)
        camadas_extra = 0
        if op < 0.999 or blur > 0.3:
            p = skia.Paint()
            p.setAlphaf(op)
            if blur > 0.3:
                p.setImageFilter(skia.ImageFilters.Blur(blur, blur))
            c.saveLayer(None, p)
            camadas_extra = 1
        if cam.get("entrada") and (cam["entrada"] if isinstance(cam["entrada"], str) else cam["entrada"].get("tipo")) == "mascara" and prog < 0.999:
            w = float(cam.get("w", 1200))
            h = float(cam.get("h", 600))
            c.clipRect(skia.Rect.MakeXYWH(-w / 2 - 40, -h / 2 - 200, (w + 80) * prog, h + 400))
            prog = 1.0
        metodo = getattr(self, "_" + str(cam.get("tipo", "")), None)
        if metodo:
            metodo(c, cam, lt, prog, ln)
        for _ in range(camadas_extra):
            c.restore()
        c.restore()

    # --- tipos ------------------------------------------------------------

    def _grupo(self, c, cam, lt, prog, ln):
        for f in cam.get("_filhos", []):
            self._camada(c, f, lt)

    def _retangulo(self, c, cam, lt, prog, ln):
        w, h = ln.valor("w", lt) or float(cam.get("w", 400)), ln.valor("h", lt) or float(cam.get("h", 200))
        w, h = max(0.0, w), max(0.0, h)
        raio = float(cam.get("raio", 28))
        estilo = cam.get("estilo", "solido")
        x0, y0 = -w / 2, -h / 2
        if cam.get("ancora") == "esquerda":
            x0 = -float(cam.get("w", w)) / 2
        if estilo == "cartao":
            cartao(c, x0, y0, w, h, raio, self.est, borda_cor=self.hexa(cam["borda"]) if cam.get("borda") else None,
                   brilho=float(cam.get("brilho", 0)), fundo=self.hexa(cam.get("cor", "cartao")) if cam.get("cor") else None)
            return
        if cam.get("sombra"):
            desfocado(c, x0, y0 + 16, w, h, raio, 30, "#000000", 0.5)
        if cam.get("brilho"):
            desfocado(c, x0, y0, w, h, raio, 20, self.hexa(cam.get("cor_brilho", cam.get("cor", "destaque"))), float(cam["brilho"]) * 0.6, 10)
        if estilo == "contorno":
            if prog < 0.999:
                self._tracar(c, skia.Path().addRRect(rrect(x0, y0, w, h, raio)), prog, tinta(self.c(cam.get("cor", "destaque")), "stroke", float(cam.get("traco", 4))))
            else:
                c.drawRRect(rrect(x0, y0, w, h, raio), tinta(self.c(cam.get("cor", "destaque")), "stroke", float(cam.get("traco", 4))))
            return
        p = skia.Paint(AntiAlias=True)
        cores = cam.get("cores")
        if cores and len(cores) >= 2:
            p.setShader(skia.GradientShader.MakeLinear([skia.Point(x0, y0), skia.Point(x0 + w, y0 + h)], [self.c(k) for k in cores[:4]]))
        else:
            p.setColor(self.c(cam.get("cor", "cartao"), float(cam.get("alfa", 1))))
        c.drawRRect(rrect(x0, y0, w, h, raio), p)
        if cam.get("borda"):
            c.drawRRect(rrect(x0, y0, w, h, raio), tinta(self.c(cam["borda"], float(cam.get("borda_alfa", 0.9))), "stroke", float(cam.get("traco", 2))))

    def _circulo(self, c, cam, lt, prog, ln):
        r = float(cam.get("raio", 60))
        if cam.get("brilho"):
            c.drawCircle(0, 0, r, tinta(self.c(cam.get("cor", "destaque"), float(cam["brilho"]) * 0.6), desfoque=r * 0.4))
        if cam.get("estilo") == "contorno":
            p = tinta(self.c(cam.get("cor", "destaque")), "stroke", float(cam.get("traco", 5)))
            if prog < 0.999:
                path = skia.Path()
                path.addArc(skia.Rect.MakeXYWH(-r, -r, 2 * r, 2 * r), -90, 360 * prog)
                c.drawPath(path, p)
            else:
                c.drawCircle(0, 0, r, p)
        else:
            c.drawCircle(0, 0, r * (prog if cam.get("entrada") == "desenhar" else 1), tinta(self.c(cam.get("cor", "destaque"), float(cam.get("alfa", 1)))))

    def _tracar(self, c, path, prog, paint, seta=False):
        pm = skia.PathMeasure(path, False)
        total = pm.getLength()
        if total <= 0:
            return
        seg = skia.Path()
        pm.getSegment(0, total * prog, seg, True)
        c.drawPath(seg, paint)
        if seta and prog > 0.02:
            pos, tan = pm.getPosTan(total * prog)
            ang = math.atan2(tan.y(), tan.x())
            tam = paint.getStrokeWidth() * 3.2
            cab = skia.Path()
            cab.moveTo(pos.x() - tam * math.cos(ang - 0.5), pos.y() - tam * math.sin(ang - 0.5))
            cab.lineTo(pos.x(), pos.y())
            cab.lineTo(pos.x() - tam * math.cos(ang + 0.5), pos.y() - tam * math.sin(ang + 0.5))
            c.drawPath(cab, paint)

    def _linha(self, c, cam, lt, prog, ln):
        pts = cam.get("pontos") or []
        if len(pts) < 2:
            return
        path = suavizar(pts) if cam.get("suave", True) else skia.Path()
        if not cam.get("suave", True):
            path.moveTo(*pts[0])
            for p in pts[1:]:
                path.lineTo(*p)
        paint = tinta(self.c(cam.get("cor", "destaque")), "stroke", float(cam.get("traco", 6)))
        if cam.get("tracejado"):
            paint.setPathEffect(skia.DashPathEffect.Make([float(cam["tracejado"]), float(cam["tracejado"])], -lt * 60))
        if cam.get("brilho"):
            g = tinta(self.c(cam.get("cor", "destaque"), 0.5), "stroke", float(cam.get("traco", 6)) * 2.5, desfoque=10)
            self._tracar(c, path, prog, g)
        self._tracar(c, path, prog, paint, bool(cam.get("seta")))
        if cam.get("pulso") and prog >= 0.999:  # luz correndo pelo trilho (movimento secundário)
            pm = skia.PathMeasure(path, False)
            pos, _ = pm.getPosTan((lt * float(cam.get("pulso_velocidade", 0.6)) % 1.0) * pm.getLength())
            c.drawCircle(pos.x(), pos.y(), float(cam.get("traco", 6)) * 1.6, tinta(self.c("branco", 0.9), desfoque=4))

    def _caminho(self, c, cam, lt, prog, ln):
        path = caminho_svg(str(cam.get("d", "")))
        esc = float(cam.get("tamanho_svg", 1))
        if esc != 1:
            c.scale(esc, esc)
        if cam.get("preencher"):
            c.drawPath(path, tinta(self.c(cam.get("cor", "destaque"), float(cam.get("alfa", 1)))))
        else:
            self._tracar(c, path, prog, tinta(self.c(cam.get("cor", "destaque")), "stroke", float(cam.get("traco", 6)) / max(esc, 0.01)), bool(cam.get("seta")))

    def _icone(self, c, cam, lt, prog, ln):
        nome = str(cam.get("nome", ""))
        tipo, _, ic = nome.partition(":")
        if not ic:
            tipo, ic = "lucide", nome
        tam = float(cam.get("tamanho", 120))
        if cam.get("fundo"):
            lado = tam * 1.7
            c.drawRRect(rrect(-lado / 2, -lado / 2, lado, lado, lado * float(cam.get("fundo_raio", 0.28))), tinta(self.c(cam["fundo"])))
        if cam.get("brilho"):
            c.drawCircle(0, 0, tam * 0.7, tinta(self.c(cam.get("cor", "destaque"), float(cam["brilho"]) * 0.45), desfoque=tam * 0.35))
        self.ic.desenhar(c, "marca" if tipo == "marca" else "lucide", ic, 0, 0, tam, self.hexa(cam.get("cor", "destaque")), float(cam.get("traco", 2)))

    def _logo(self, c, cam, lt, prog, ln):
        self.ic.app(c, cam, 0, 0, float(cam.get("tamanho", 160)), self.est)

    def _imagem(self, c, cam, lt, prog, ln):
        img = self.cena.imagem_logo() if cam.get("arquivo") in (None, "logo_cliente") else self.ic.imagem_logo(cam.get("arquivo"))
        if img is None:
            return
        w = float(cam.get("w", 300))
        h = w * img.height() / max(1, img.width())
        if cam.get("raio"):
            c.clipRRect(rrect(-w / 2, -h / 2, w, h, float(cam["raio"])), True)
        imagem(c, img, -w / 2, -h / 2, w, h)

    def _particulas(self, c, cam, lt, prog, ln):
        vel = float(cam.get("velocidade", 40))
        tam = float(cam.get("tamanho", 4))
        area = cam.get("area") or [0, 0, 1080, self.H]
        x0 = float(cam.get("x", 540))
        y0 = float(cam.get("y", 960))
        for (px, py, s, fase_) in cam["_p"]:
            yy = area[1] + ((py - area[1] - lt * vel * s) % max(1.0, area[3]))
            xx = px + math.sin(lt * 0.8 + fase_) * 14
            a = 0.25 + 0.55 * s * (0.6 + 0.4 * math.sin(lt * 2 + fase_))
            c.drawCircle(xx - x0, yy - y0, tam * s, tinta(self.c(cam.get("cor", "destaque"), a * float(cam.get("alfa", 1))), desfoque=tam * 0.6))

    def _barra(self, c, cam, lt, prog, ln):
        w, h = float(cam.get("w", 600)), float(cam.get("h", 18))
        c.drawRRect(rrect(-w / 2, -h / 2, w, h, h / 2), tinta(self.c(cam.get("trilho", "#2A2A2F"))))
        if prog > 0:
            if cam.get("brilho", True):
                c.drawRRect(rrect(-w / 2, -h / 2, w * prog, h, h / 2), tinta(self.c(cam.get("cor", "destaque"), 0.55), desfoque=h * 0.7))
            c.drawRRect(rrect(-w / 2, -h / 2, w * prog, h, h / 2), tinta(self.c(cam.get("cor", "destaque"))))

    def _numero(self, c, cam, lt, prog, ln):
        de, ate = float(cam.get("de", 0)), float(cam.get("ate", 100))
        em, du = float(cam.get("contar_em", 0)), float(cam.get("contar_dur", 1.4))
        v = mix(de, ate, curva(cam.get("curva_contagem", "expo.out"))(clamp((lt - em) / max(0.01, du))))
        dec = int(cam.get("decimais", 0))
        num = ("{:,.%df}" % dec).format(v).replace(",", "X").replace(".", ",").replace("X", ".")
        cam2 = dict(cam)
        cam2["texto"] = "%s%s%s" % (cam.get("prefixo", ""), num, cam.get("sufixo", ""))
        cam2.pop("palavras", None)
        self._texto(c, cam2, lt, 1.0, ln)

    def _texto(self, c, cam, lt, prog, ln):
        texto = str(cam.get("texto", ""))
        if cam.get("caixa_alta"):
            texto = texto.upper()
        tam = float(cam.get("tamanho", 80))
        maxw = float(cam.get("largura_max", 940))
        fonte = self.f.fonte(cam.get("fonte", "forte"), tam)
        espaco = float(cam.get("espacamento", 0))
        linhas = []
        for bloco in texto.split("\n"):
            linhas += quebrar(fonte, bloco, maxw) if cam.get("quebrar", True) else [bloco]
        # Encolhe se a palavra maior não couber.
        maior = max([largura(fonte, l, espaco) for l in linhas] or [0])
        if maior > maxw > 0:
            tam *= maxw / maior
            fonte = self.f.fonte(cam.get("fonte", "forte"), tam)
        lh = tam * float(cam.get("entrelinha", 1.12))
        alinhar = cam.get("alinhar", "centro")
        cor_t = cam.get("cor", "texto")
        destaques = {re.sub(r"[^\w]", "", d.lower()) for d in (cam.get("destaque") or [])}
        tempos = cam.get("palavras")  # tempos (s desde o início da cena) de cada palavra: entra na fala
        modo = (cam.get("entrada") if isinstance(cam.get("entrada"), str) else (cam.get("entrada") or {}).get("tipo")) or ""
        total = sum(len(l) for l in linhas) or 1
        mostrar = int(round(total * prog)) if modo in ("digitar", "letras") else total
        if cam.get("fundo_pilula"):
            w = max([largura(fonte, l, espaco) for l in linhas] or [0]) + tam * 1.1
            h = lh * len(linhas) + tam * 0.5
            c.drawRRect(rrect(-w / 2, -h / 2, w, h, min(h / 2, float(cam.get("raio", h / 2)))), tinta(self.c(cam["fundo_pilula"], float(cam.get("fundo_alfa", 0.94)))))
            if cam.get("borda"):
                c.drawRRect(rrect(-w / 2, -h / 2, w, h, min(h / 2, float(cam.get("raio", h / 2)))), tinta(self.c(cam["borda"], 0.6), "stroke", 2))
        y0 = -lh * (len(linhas) - 1) / 2 + tam * 0.35
        contador = 0
        k_palavra = 0
        for i, l in enumerate(linhas):
            palavras = l.split(" ")
            larg = [largura(fonte, w, espaco) for w in palavras]
            esp = fonte.measureText(" ")
            yy = y0 + i * lh
            if tempos:
                # Palavra por palavra na fala; a linha desliza para continuar centrada.
                vis = [j for j in range(len(palavras)) if k_palavra + j < len(tempos) and lt >= float(tempos[k_palavra + j])]
                cheia = sum(larg) + esp * (len(palavras) - 1)
                atual = sum(larg[j] for j in vis) + esp * max(0, len(vis) - 1)
                ult = max(vis) if vis else -1
                t_ult = float(tempos[k_palavra + ult]) if ult >= 0 else 0
                anterior = sum(larg[j] for j in vis if j != ult) + esp * max(0, len(vis) - 2) if ult >= 0 else 0
                q = curva("power3.out")(clamp((lt - t_ult) / 0.45))
                vis_w = mix(anterior, atual, q) if alinhar == "centro" else cheia
                xx = -vis_w / 2 if alinhar == "centro" else (-cheia / 2 if alinhar == "centro" else 0)
            else:
                cheia = sum(larg) + esp * (len(palavras) - 1)
                xx = -cheia / 2 if alinhar == "centro" else (0 if alinhar == "esq" else -cheia)
            for j, w in enumerate(palavras):
                n_letras = len(w)
                if modo in ("digitar", "letras") and contador >= mostrar:
                    break
                vis_txt = w[: max(0, mostrar - contador)] if modo in ("digitar", "letras") else w
                contador += n_letras + 1
                a_p = 1.0
                dy = 0.0
                esc_p = 1.0
                if tempos:
                    idx = k_palavra + j
                    tp = float(tempos[idx]) if idx < len(tempos) else 0.0
                    if lt < tp:
                        xx += larg[j] + esp
                        continue
                    q = clamp((lt - tp) / 0.3)
                    a_p = curva("expo.out")(q)
                    dy = mix(26, 0, curva("expo.out")(q))
                eh_dest = re.sub(r"[^\w]", "", w.lower()) in destaques
                cc = self.c("destaque" if eh_dest else cor_t, a_p)
                if eh_dest and tempos:  # punch na palavra-chave na hora dita
                    q = clamp((lt - float(tempos[k_palavra + j])) / 0.35)
                    esc_p = 1 + 0.18 * math.sin(q * math.pi)
                c.save()
                c.translate(xx + larg[j] / 2, yy + dy)
                if esc_p != 1:
                    c.scale(esc_p, esc_p)
                brilho = (self.c("destaque", 0.7 * a_p), tam * 0.18) if (eh_dest or cam.get("brilho")) else None
                escrever(c, vis_txt, -larg[j] / 2, 0, fonte, cc, espaco=espaco, brilho=brilho, sombra=float(cam.get("sombra", 10 if not self.cheia else 0)))
                c.restore()
                xx += larg[j] + esp
            k_palavra += len(palavras)
        if modo == "digitar" and prog < 1 and (lt % 0.9) < 0.5:
            c.drawRect(skia.Rect.MakeXYWH(xx + 6 if linhas else 0, y0 + (len(linhas) - 1) * lh - tam * 0.75, max(3, tam * 0.06), tam * 0.9), tinta(self.c(cor_t, 0.9)))
