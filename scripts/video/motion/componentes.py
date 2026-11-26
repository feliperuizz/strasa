"""
Componentes de motion do Estúdio de vídeo. Cada um é uma classe com
desenhar(c, lt, pe, ps, t): lt = segundos desde que entrou, pe = progresso
da entrada (0→1), ps = progresso da saída (0→1), t = tempo do vídeo.

Linguagem de movimento (regras dos editores com Claude que viralizaram):
nada de fade puro — entrada com overshoot (out_back), mola ou revelação por
máscara; itens em cascata (stagger); cada sub-item entra na sua palavra;
saída curta (~0,28 s) para não brigar com o próximo elemento.
"""
import math
import re

import skia

from base import (Elemento, brilho_radial, caber, cartao, clamp, cor, desfocado, escrever, fase, grupo, imagem, in_out, largura,
                  luminancia, mix, out_back, out_cubic, out_elastic, out_expo, quebrar, rrect, tinta)


def entrada_padrao(pe, ps, dy=60.0):
    """Alfa, escala e deslocamento da entrada/saída padrão de um cartão."""
    a = out_cubic(pe) * (1 - out_cubic(ps))
    esc = mix(0.9, 1.0, out_back(pe, 1.4)) * mix(1.0, 0.96, ps)
    return a, esc, mix(dy, 0, out_expo(pe)) - 40 * out_cubic(ps)


def pilula_tag(el, c, cx, cy, texto, p=1.0, escala=1.0, alfa=1.0):
    """'● ESTE VÍDEO': pílula escura, ponto e texto na cor de acento."""
    est = el.est
    f = el.f.fonte("forte", 32 * escala)
    tw = largura(f, texto, 2.2 * escala)
    h = 74 * escala
    w = tw + 96 * escala
    x, y = cx - w / 2, cy - h / 2
    with grupo(c, cx, cy, alfa * out_cubic(p), mix(0.7, 1, out_back(p, 2.0))):
        desfocado(c, x, y + 10, w, h, h / 2, 22, "#000000", 0.45)
        c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor("#16110D", 0.94)))
        c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor(est.destaque, 0.35), "stroke", 2.0 * escala))
        pulso = 0.75 + 0.25 * math.sin(el.cena_t * 6.0) if hasattr(el, "cena_t") else 1.0
        c.drawCircle(x + 38 * escala, cy, 9 * escala, tinta(cor(est.destaque, 0.5 * pulso), desfoque=8 * escala))
        c.drawCircle(x + 38 * escala, cy, 7.5 * escala, tinta(cor(est.destaque)))
        escrever(c, texto, x + 60 * escala, cy + 11.5 * escala, f, cor(est.destaque), espaco=2.2 * escala)
    return w, h


class Pilula(Elemento):
    entrada = 0.4

    def desenhar(self, c, lt, pe, ps, t):
        self.cena_t = t
        cx, cy = self.centro("topo", 80)
        texto = (self.d.get("texto") or "").upper()
        pilula_tag(self, c, cx, cy - 30 * out_cubic(ps), texto, pe, 1.0 + 0.15 * (self.d.get("tamanho") == "g"), 1 - ps)


class Janela(Elemento):
    """Janela de terminal/app: comando digitado, progresso e ✓ pronto."""

    LH = 74

    def preparar(self):
        self.linhas = (self.d.get("linhas") or [])[:6]
        # Cada linha começa no tempo dela (palavra-gatilho) ou em sequência.
        tempos, t = [], self.inicio + 0.35
        for i, ln in enumerate(self.linhas):
            t = float(ln["t"]) if ln.get("t") is not None else t
            tempos.append(t)
            dur = len(ln.get("texto", "")) / 24.0 + 0.25 if ln.get("tipo") == "comando" else 1.5 if ln.get("tipo") == "progresso" else 0.4
            ln["_dur"] = float(ln.get("duracao") or dur)
            t = t + ln["_dur"] + 0.15
        self.tempos = tempos

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        w = 900
        hcheia = 92 + 40 + self.LH * max(1, len(self.linhas))
        cx, cy = self.centro("topo", hcheia)
        x, y = cx - w / 2, cy - hcheia / 2
        # A janela cresce quando cada linha entra (mola curta), em vez de nascer vazia.
        vis = sum(out_back(fase(t, ti, 0.32), 1.2) for ti in self.tempos)
        h = 92 + 40 + self.LH * max(1.0, vis)
        a, esc, dy = entrada_padrao(pe, ps)
        with grupo(c, cx, y + h / 2, a, esc, dy=dy):
            cartao(c, x, y, w, h, 30, est)
            # Barra de título.
            for i in range(3):
                c.drawCircle(x + 40 + i * 30, y + 46, 8.5, tinta(cor("#FFFFFF", 0.16)))
            ft = self.f.fonte("mono", 27)
            escrever(c, self.d.get("titulo") or "claude — ~/videos/reel", x + 150, y + 56, ft, cor(est.suave))
            c.drawLine(x, y + 92, x + w, y + 92, tinta(cor("#FFFFFF", 0.07), "stroke", 2))
            yy = y + 92 + 26
            for i, ln in enumerate(self.linhas):
                t0 = self.tempos[i]
                if t < t0:
                    break
                p = fase(t, t0, 0.22)
                base = yy + i * self.LH + 50
                with grupo(c, x + 60, base - 14, out_cubic(p), 1.0, dx=mix(-24, 0, out_expo(p))):
                    self.linha(c, ln, x, base, w, t - t0, t)
        return

    def linha(self, c, ln, x, base, w, lt, t):
        est = self.est
        tipo = ln.get("tipo", "texto")
        texto = ln.get("texto", "")
        if tipo == "comando":
            f = self.f.fonte("forte", 42)
            escrever(c, ">", x + 44, base, f, cor(est.destaque))
            n = int(clamp(lt * 24.0, 0, len(texto)))
            vis = texto[:n]
            wv = escrever(c, vis, x + 92, base, f, cor(est.texto))
            if n < len(texto) or (lt % 1.0) < 0.55:
                c.drawRect(skia.Rect.MakeXYWH(x + 96 + wv, base - 34, 4, 42), tinta(cor(est.texto, 0.85)))
        elif tipo == "progresso":
            f = self.f.fonte("texto", 36)
            p = in_out(clamp(lt / max(0.3, ln["_dur"])))
            c.save()
            c.translate(x + 62, base - 13)
            c.rotate(lt * 220)
            self.ic.desenhar(c, "lucide", "asterisk", 0, 0, 34, est.suave, 2.4)
            c.restore()
            escrever(c, texto or "editando…", x + 96, base, f, cor(est.suave))
            bx, bw = x + w - 60 - 330, 330
            c.drawRRect(rrect(bx, base - 20, bw, 12, 6), tinta(cor(est.trilho)))
            if p > 0:
                c.drawRRect(rrect(bx, base - 20, bw * p, 12, 6), tinta(cor(est.destaque, 0.6), desfoque=8))
                c.drawRRect(rrect(bx, base - 20, bw * p, 12, 6), tinta(cor(est.destaque)))
        elif tipo == "ok":
            f = self.f.fonte("forte", 40)
            p = out_back(clamp(lt / 0.35), 2.2)
            with grupo(c, x + 62, base - 13, 1.0, p):
                self.ic.desenhar(c, "lucide", "check", x + 62, base - 13, 40, est.destaque, 3.2)
            escrever(c, texto or "pronto", x + 96, base, f, cor(est.texto))
        else:
            f = self.f.fonte("texto", 38)
            escrever(c, texto, x + 60, base, f, cor(est.texto, 0.9))


class Contador(Elemento):
    """Número gigante contando, com brilho (o '100%')."""

    entrada = 0.5

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        d = self.d
        de, ate = float(d.get("de", 0)), float(d.get("ate", 100))
        conta = min(1.8, max(0.6, self.duracao - 0.9))
        p = out_expo(clamp((lt - 0.1) / conta))
        v = mix(de, ate, p)
        dec = int(d.get("decimais", 0))
        num = ("{:,.%df}" % dec).format(v).replace(",", "X").replace(".", ",").replace("X", ".")
        s = "%s%s%s" % (d.get("prefixo", ""), num, d.get("sufixo", ""))
        a = out_cubic(pe) * (1 - out_cubic(ps))
        cx, cy = self.centro("centro", 360)
        if d.get("fundo", "escuro") == "escuro":
            c.drawRect(skia.Rect.MakeWH(self.W, self.H), tinta(cor("#060606", 0.86 * a)))
        brilho_radial(c, cx, cy - 40, 560, est.destaque, 0.42 * a)
        fim = fase(lt, 0.1 + conta, 0.3)
        esc = mix(0.55, 1, out_back(pe, 1.6)) * (1 + 0.06 * math.sin(fim * math.pi))
        f = caber(self.f, "titulo", s, self.W * 0.86, 290)
        with grupo(c, cx, cy, a, esc):
            escrever(c, s, cx, cy + f.getSize() * 0.36, f, cor(est.destaque), "centro", brilho=(cor(est.destaque, 0.75), 26))
            if d.get("texto"):
                fl = self.f.fonte("forte", 46)
                escrever(c, d["texto"], cx, cy + f.getSize() * 0.36 + 100, fl, cor(est.texto), "centro", sombra=10)


class Rec(Elemento):
    """Moldura de câmera: cantos, ● REC piscando e timecode."""

    entrada = 0.4

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        a = out_cubic(pe) * (1 - out_cubic(ps))
        x0, x1 = self.W * 0.075, self.W * 0.925
        y0, y1 = self.H * 0.11, self.H * 0.75
        off = mix(46, 0, out_expo(pe))
        L, esp = 96, 7
        p = tinta(cor(est.destaque, a), "stroke", esp)
        for (x, y, sx, sy) in ((x0, y0, 1, 1), (x1, y0, -1, 1), (x0, y1, 1, -1), (x1, y1, -1, -1)):
            xx, yy = x - sx * off, y - sy * off
            path = skia.Path()
            path.moveTo(xx, yy + sy * L)
            path.lineTo(xx, yy)
            path.lineTo(xx + sx * L, yy)
            c.drawPath(path, p)
        f = self.f.fonte("forte", 34)
        mono = self.f.fonte("mono", 32)
        pisca = 1.0 if (lt % 1.0) < 0.6 else 0.25
        c.drawCircle(x0 + 44, y0 + 54, 10, tinta(cor(est.destaque, a * pisca)))
        escrever(c, "REC", x0 + 66, y0 + 66, f, cor("#FFFFFF", a), sombra=8)
        fr = int(lt * 30)
        tc = "00:%02d:%02d:%02d" % (fr // 1800 % 60, fr // 30 % 60, fr % 30)
        escrever(c, tc, x1 - 40, y0 + 66, mono, cor("#FFFFFF", 0.92 * a), "dir", sombra=8)


class Apps(Elemento):
    """Fileira de ícones de apps/marcas; ação: riscar (✗), check (✓) ou destacar."""

    def preparar(self):
        self.itens = (self.d.get("itens") or [])[:4]

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        n = max(1, len(self.itens))
        lado = 230 if n <= 2 else 200 if n == 3 else 178
        gap = 40
        total = n * lado + (n - 1) * gap
        cx, cy = self.centro("topo", lado)
        x = cx - total / 2
        sai = 1 - out_cubic(ps)
        acao = self.d.get("acao", "nenhum")
        for i, it in enumerate(self.itens):
            ti = self.t_item(it, i, 0.0, 0.14)
            p = fase(t, ti, 0.45)
            if p <= 0:
                continue
            ix = x + i * (lado + gap)
            icx, icy = ix + lado / 2, cy
            with grupo(c, icx, icy, out_cubic(fase(t, ti, 0.2)) * sai, mix(0.4, 1, out_back(p, 2.0)), dy=-30 * out_cubic(ps)):
                cartao(c, ix, cy - lado / 2, lado, lado, lado * 0.2, est)
                self.ic.app(c, it, icx, cy - lado * 0.08, lado * 0.46, est)
                fn = caber(self.f, "forte", it.get("nome", ""), lado * 0.86, 30)
                escrever(c, it.get("nome", ""), icx, cy + lado * 0.38, fn, cor(est.texto, 0.92), "centro")
                # Ação depois que todos entraram (ou no tempo dela).
                ta = float(self.d["t_acao"]) if self.d.get("t_acao") is not None else self.inicio + 0.25 + n * 0.14 + 0.35
                pa = fase(t, ta + i * 0.12, 0.28)
                if acao == "riscar" and pa > 0:
                    x0, y0 = ix + lado * 0.1, cy + lado * 0.16
                    x1, y1 = x0 + (lado * 0.8) * out_cubic(pa), y0 - (lado * 0.42) * out_cubic(pa)
                    c.drawLine(x0, y0, x1, y1, tinta(cor("#000000", 0.5), "stroke", 14, desfoque=6))
                    c.drawLine(x0, y0, x1, y1, tinta(cor(est.destaque), "stroke", 10))
                elif acao == "check" and pa > 0:
                    with grupo(c, ix + lado - 20, cy - lado / 2 + 20, 1, out_back(pa, 2.4)):
                        c.drawCircle(ix + lado - 20, cy - lado / 2 + 20, 30, tinta(cor(est.destaque)))
                        self.ic.desenhar(c, "lucide", "check", ix + lado - 20, cy - lado / 2 + 20, 34, "#FFFFFF", 3.4)


class Comentario(Elemento):
    """'● COMENTE' + caixa de comentário digitando a palavra + botão de enviar."""

    def desenhar(self, c, lt, pe, ps, t):
        self.cena_t = t
        est = self.est
        palavra = (self.d.get("texto") or "EU QUERO").upper()
        rotulo = (self.d.get("rotulo") or "COMENTE").upper()
        w, h = 900, 150
        cx, cy = self.centro("baixo", h + 120)
        x, y = cx - w / 2, cy - h / 2
        a, esc, dy = entrada_padrao(pe, ps, 80)
        with grupo(c, cx, cy, a, esc, dy=dy):
            pilula_tag(self, c, cx, y - 64, rotulo, fase(lt, 0.15, 0.4), 0.82)
            desfocado(c, x, y + 16, w, h, h / 2, 30, "#000000", 0.5)
            desfocado(c, x, y, w, h, h / 2, 16, est.destaque, 0.45, 12)
            c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor("#141210", 0.95)))
            c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor(est.destaque, 0.9), "stroke", 3))
            c.drawCircle(x + 80, cy, 46, tinta(cor("#2B2B30")))
            self.ic.desenhar(c, "lucide", "user-round", x + 80, cy, 50, "#8E8E96", 2.0)
            f = caber(self.f, "forte", palavra, w - 330, 62)
            t0 = 0.45
            n = int(clamp((lt - t0) * 11.0, 0, len(palavra)))
            wv = escrever(c, palavra[:n], x + 152, cy + f.getSize() * 0.36, f, cor(est.destaque))
            if (lt % 0.9) < 0.5 or n < len(palavra):
                c.drawRect(skia.Rect.MakeXYWH(x + 158 + wv, cy - 32, 4, 64), tinta(cor(est.texto, 0.9)))
            # Botão de enviar: aperta quando a palavra termina.
            tp = t0 + len(palavra) / 11.0 + 0.25
            pp = fase(lt, tp, 0.35)
            bx = x + w - 84
            bes = 1 - 0.16 * math.sin(pp * math.pi)
            if pp > 0:
                c.drawCircle(bx, cy, 52 + 40 * out_cubic(pp), tinta(cor(est.destaque, 0.5 * (1 - pp)), "stroke", 4))
            with grupo(c, bx, cy, 1, bes):
                c.drawCircle(bx, cy, 54, tinta(cor(est.destaque)))
                self.ic.desenhar(c, "lucide", "send", bx - 2, cy + 2, 46, "#1A1A1A", 2.4)


class Passos(Elemento):
    """Cartão 'PASSO A PASSO': itens numerados (cada um na sua palavra) + bloco de código."""

    def preparar(self):
        self.itens = (self.d.get("itens") or [])[:5]
        self.codigo = (self.d.get("codigo") or [])[:3]

    def desenhar(self, c, lt, pe, ps, t):
        self.cena_t = t
        est = self.est
        w = 880
        lh = 84
        hc = (40 + 54 * len(self.codigo)) if self.codigo else 0
        hcheia = 150 + lh * len(self.itens) + (hc + 30 if hc else 10)
        cx, cy = self.centro("topo", hcheia)
        x, y = cx - w / 2, cy - hcheia / 2
        # O cartão cresce a cada item (e quando o código aparece): nunca fica vazio esperando a fala.
        tempos = [self.t_item(it, i, 0.4, 0.45) for i, it in enumerate(self.itens)]
        vis = sum(out_back(fase(t, ti, 0.35), 1.2) for ti in tempos)
        pcod = out_cubic(fase(t, max(tempos or [self.inicio]) + 0.5, 0.3)) if hc else 0
        h = 150 + lh * vis + (hc + 30) * pcod + 10 * (1 - pcod)
        a, esc, dy = entrada_padrao(pe, ps)
        with grupo(c, cx, y + h / 2, a, esc, dy=dy):
            cartao(c, x, y, w, h, 30, est)
            titulo = (self.d.get("texto") or "PASSO A PASSO").upper()
            ft = self.f.fonte("forte", 30)
            tw = largura(ft, titulo, 2) + 80
            c.drawRRect(rrect(x + 40, y + 40, tw, 62, 31), tinta(cor(est.destaque, 0.12)))
            c.drawRRect(rrect(x + 40, y + 40, tw, 62, 31), tinta(cor(est.destaque, 0.5), "stroke", 2))
            c.drawCircle(x + 70, y + 71, 7, tinta(cor(est.destaque)))
            escrever(c, titulo, x + 88, y + 82, ft, cor(est.destaque), espaco=2)
            fi = self.f.fonte("forte", 42)
            fn = self.f.fonte("forte", 30)
            ultimo = self.inicio
            for i, it in enumerate(self.itens):
                ti = self.t_item(it, i, 0.4, 0.45)
                ultimo = max(ultimo, ti)
                p = fase(t, ti, 0.4)
                if p <= 0:
                    continue
                by = y + 150 + i * lh + 40
                with grupo(c, x + 70, by - 14, out_cubic(fase(t, ti, 0.2)), 1, dx=mix(-36, 0, out_expo(p))):
                    with grupo(c, x + 70, by - 14, 1, mix(0.3, 1, out_back(p, 2.2))):
                        c.drawCircle(x + 70, by - 14, 27, tinta(cor(est.destaque)))
                        escrever(c, str(i + 1), x + 70, by - 3, fn, cor("#FFFFFF" if luminancia(est.destaque) < 0.6 else "#111111"), "centro")
                    escrever(c, it.get("texto", ""), x + 118, by, fi, cor(est.texto))
            if self.codigo:
                cy0 = y + 150 + lh * len(self.itens) + 10
                tc = ultimo + 0.5
                pc = fase(t, tc, 0.3)
                if pc > 0:
                    c.drawRRect(rrect(x + 40, cy0, w - 80, hc, 18), tinta(cor("#000000", 0.45 * pc)))
                    c.drawRRect(rrect(x + 40, cy0, w - 80, hc, 18), tinta(cor("#FFFFFF", 0.06 * pc), "stroke", 2))
                    fm = self.f.fonte("mono", 30)
                    tl = tc + 0.2
                    for j, ln in enumerate(self.codigo):
                        n = int(clamp((t - tl) * 26, 0, len(ln)))
                        escrever(c, ">", x + 70, cy0 + 56 + j * 54, fm, cor(est.destaque, pc))
                        escrever(c, ln[:n], x + 104, cy0 + 56 + j * 54, fm, cor(est.texto, 0.85 * pc))
                        tl += len(ln) / 26.0 + 0.15


class Cards(Elemento):
    """Fileira de cartões verticais com ícone e rótulo; o destaque passa de um para o outro."""

    def preparar(self):
        self.itens = (self.d.get("itens") or [])[:4]

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        n = max(1, len(self.itens))
        cw = 250 if n <= 3 else 210
        ch = cw * 1.42
        gap = 30
        total = n * cw + (n - 1) * gap
        cx, cy = self.centro("topo", ch + 80)
        x = cx - total / 2
        sai = 1 - out_cubic(ps)
        # Destaque: o último item cujo tempo já chegou (ou o pedido fixo).
        tempos = [self.t_item(it, i, 0.5, 0.0 if self.d.get("destaque") is not None else 0.9) for i, it in enumerate(self.itens)]
        ativo = self.d.get("destaque")
        if ativo is None:
            ativo = max([i for i, ti in enumerate(tempos) if t >= ti] or [-1])
        for i, it in enumerate(self.itens):
            te = self.inicio + i * 0.1
            p = fase(t, te, 0.5)
            if p <= 0:
                continue
            ix = x + i * (cw + gap)
            on = i == ativo
            pon = fase(t, tempos[i], 0.3) if on else 0
            icx = ix + cw / 2
            with grupo(c, icx, cy, out_cubic(fase(t, te, 0.25)) * sai, mix(0.85, 1, out_back(p, 1.8)) * (1 + 0.05 * out_back(pon, 2)), dy=mix(70, 0, out_expo(p)) - 30 * out_cubic(ps)):
                cartao(c, ix, cy - ch / 2, cw, ch, 28, est, borda_cor=est.destaque if on else None, borda=3 if on else 2, brilho=pon)
                self.ic.desenhar(c, "lucide", "play", ix + cw - 34, cy - ch / 2 + 34, 24, "#FFFFFF" if not on else est.destaque, 2.2)
                corico = est.destaque if on else "#D5D5DB"
                if it.get("icone") == "antes-depois":
                    self.antes_depois(c, ix, cy, cw, ch, on)
                elif it.get("logo") or it.get("imagem"):
                    self.ic.app(c, it, icx, cy - 20, cw * 0.38, est)
                else:
                    self.ic.desenhar(c, "lucide", it.get("icone") or "image", icx, cy - 20, cw * 0.42, corico, 1.8)
                c.drawRRect(rrect(ix + 24, cy + ch / 2 - 34, cw - 48, 8, 4), tinta(cor(est.trilho)))
                prog = 0.35 + 0.65 * pon if on else 0.35
                c.drawRRect(rrect(ix + 24, cy + ch / 2 - 34, (cw - 48) * prog, 8, 4), tinta(cor(est.destaque if on else "#5A5A62")))
                fl = caber(self.f, "forte", it.get("texto", ""), cw + gap - 6, 32)
                linhas = quebrar(fl, it.get("texto", ""), cw + gap - 6)[:2]
                for k, l in enumerate(linhas):
                    escrever(c, l, icx, cy + ch / 2 + 50 + k * 38, fl, cor(est.destaque if on else est.texto, 1 if on else 0.85), "centro", sombra=10)

    def antes_depois(self, c, ix, cy, cw, ch, on):
        est = self.est
        mx = ix + cw / 2
        c.drawLine(mx, cy - ch / 2 + 70, mx, cy + ch / 2 - 60, tinta(cor(est.destaque), "stroke", 4))
        self.ic.desenhar(c, "lucide", "user-round", ix + cw * 0.25, cy - 30, 64, "#9A9AA2", 2)
        self.ic.desenhar(c, "lucide", "user-round", ix + cw * 0.75, cy - 30, 64, est.destaque, 2)
        f = self.f.fonte("forte", 22)
        escrever(c, "ANTES", ix + cw * 0.25, cy + 40, f, cor("#9A9AA2"), "centro")
        escrever(c, "DEPOIS", ix + cw * 0.75, cy + 40, f, cor(est.destaque), "centro")


class Notificacao(Elemento):
    """Notificação de celular descendo do topo."""

    entrada = 0.55

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        w, h = 960, 196
        cx = self.W / 2
        y = mix(-h - 40, self.H * 0.085, out_back(pe, 1.2)) - (h + 140) * in_out(ps)
        x = cx - w / 2
        cartao(c, x, y, w, h, 48, est, fundo="#1E1E21")
        self.ic.app(c, self.d, x + 82, y + h / 2, 84, est) if (self.d.get("logo") or self.d.get("sigla") or self.d.get("imagem")) else self.logo_cliente(c, x + 82, y + h / 2, 92)
        fa = self.f.fonte("texto", 27)
        escrever(c, (self.d.get("app") or "MENSAGEM").upper(), x + 150, y + 58, fa, cor(est.suave), espaco=1.5)
        escrever(c, self.d.get("quando") or "agora", x + w - 40, y + 58, fa, cor(est.suave), "dir")
        ft = caber(self.f, "forte", self.d.get("texto", ""), w - 200, 40)
        escrever(c, self.d.get("texto", ""), x + 150, y + 110, ft, cor(est.texto))
        fs = self.f.fonte("texto", 32)
        sub = self.d.get("subtexto", "")
        while sub and fs.measureText(sub) > w - 200:
            sub = sub[:-2].rstrip() + "…" if not sub.endswith("…") else sub[:-2] + "…"
        escrever(c, sub, x + 150, y + 156, fs, cor(est.texto, 0.75))

    def logo_cliente(self, c, cx, cy, lado):
        img = self.cena.imagem_logo()
        c.drawRRect(rrect(cx - lado / 2, cy - lado / 2, lado, lado, lado * 0.24), tinta(cor(self.est.primaria)))
        if img is not None:
            s = lado * 0.72 / max(img.width(), img.height())
            iw, ih = img.width() * s, img.height() * s
            imagem(c, img, cx - iw / 2, cy - ih / 2, iw, ih)


class Grafico(Elemento):
    """Barras horizontais crescendo, com o valor contando; a maior acende."""

    def preparar(self):
        self.itens = (self.d.get("itens") or [])[:5]

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        w = 900
        lh = 112
        h = 120 + lh * len(self.itens)
        cx, cy = self.centro("topo", h)
        x, y = cx - w / 2, cy - h / 2
        a, esc, dy = entrada_padrao(pe, ps)
        maxv = max([float(i.get("valor", 0)) for i in self.itens] or [1]) or 1
        suf = self.d.get("sufixo", "")
        with grupo(c, cx, cy, a, esc, dy=dy):
            cartao(c, x, y, w, h, 30, est)
            ft = self.f.fonte("forte", 36)
            escrever(c, self.d.get("texto", ""), x + 44, y + 74, ft, cor(est.texto))
            fl = self.f.fonte("texto", 30)
            fv = self.f.fonte("forte", 34)
            for i, it in enumerate(self.itens):
                ti = self.t_item(it, i, 0.35, 0.18)
                p = out_expo(fase(t, ti, 0.9))
                by = y + 120 + i * lh
                v = float(it.get("valor", 0))
                top = v == maxv
                escrever(c, it.get("rotulo", ""), x + 44, by + 34, fl, cor(est.suave if not top else est.texto))
                bw = (w - 88 - 150) * (v / maxv) * p
                c.drawRRect(rrect(x + 44, by + 50, w - 88 - 150, 26, 13), tinta(cor(est.trilho)))
                if bw > 1:
                    if top:
                        c.drawRRect(rrect(x + 44, by + 50, bw, 26, 13), tinta(cor(est.destaque, 0.55), desfoque=12))
                    c.drawRRect(rrect(x + 44, by + 50, bw, 26, 13), tinta(cor(est.destaque if top else "#6B6B73")))
                val = mix(0, v, p)
                txt = (("%.0f" % val) if v == int(v) else ("%.1f" % val).replace(".", ",")) + suf
                escrever(c, txt, x + w - 44, by + 74, fv, cor(est.destaque if top else est.texto), "dir")


class Comparacao(Elemento):
    """Duas colunas: o jeito errado (✗, apagado) e o certo (✓, na cor de acento)."""

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        esq = (self.d.get("esquerda") or {})
        dirc = (self.d.get("direita") or {})
        ie, idr = (esq.get("itens") or [])[:4], (dirc.get("itens") or [])[:4]
        n = max(len(ie), len(idr), 1)
        cw, gap = 430, 24
        h = 150 + n * 84
        cx, cy = self.centro("topo", h)
        a, esc, dy = entrada_padrao(pe, ps)
        with grupo(c, cx, cy, a, esc, dy=dy):
            for lado, dados, itens in ((0, esq, ie), (1, dirc, idr)):
                x = cx - cw - gap / 2 if lado == 0 else cx + gap / 2
                pl = fase(t, self.inicio + 0.12 * lado, 0.45)
                with grupo(c, x + cw / 2, cy, 1, mix(0.9, 1, out_back(pl))):
                    cartao(c, x, cy - h / 2, cw, h, 28, est, borda_cor=est.destaque if lado else None, borda=3 if lado else 2, brilho=0.6 * lado)
                    ft = self.f.fonte("forte", 36)
                    corh = est.destaque if lado else est.suave
                    self.ic.desenhar(c, "lucide", "circle-check" if lado else "circle-x", x + 56, cy - h / 2 + 62, 40, corh, 2.4)
                    escrever(c, (dados.get("titulo") or ("DEPOIS" if lado else "ANTES")).upper(), x + 90, cy - h / 2 + 75, ft, cor(corh), espaco=1.5)
                    fi = self.f.fonte("texto" if not lado else "forte", 32)
                    for i, it in enumerate(itens):
                        texto = it.get("texto", "") if isinstance(it, dict) else str(it)
                        ti = self.t_item(it if isinstance(it, dict) else {}, i * 2 + lado, 0.4, 0.3)
                        p = fase(t, ti, 0.35)
                        if p <= 0:
                            continue
                        by = cy - h / 2 + 150 + i * 84
                        with grupo(c, x + 40, by, out_cubic(p), 1, dx=mix(-20, 0, out_expo(p))):
                            linhas = quebrar(fi, texto, cw - 70)[:2]
                            for k, l in enumerate(linhas):
                                escrever(c, l, x + 40, by + k * 36, fi, cor(est.texto, 0.95 if lado else 0.6))


class Seguir(Elemento):
    """Chamada final: perfil + botão Seguir, com cursor que clica."""

    entrada = 0.5

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        w, h = 860, 170
        cx, cy = self.centro("centro", h)
        x, y = cx - w / 2, cy - h / 2
        a, esc, dy = entrada_padrao(pe, ps, 90)
        tc = min(1.5, self.duracao * 0.45)  # clique
        clicou = lt >= tc
        with grupo(c, cx, cy, a, esc, dy=dy):
            cartao(c, x, y, w, h, h / 2, est)
            Notificacao.logo_cliente(self, c, x + 92, cy, 112)
            fh = caber(self.f, "forte", self.d.get("texto", "@perfil"), 380, 42)
            escrever(c, self.d.get("texto", "@perfil"), x + 172, cy - 4, fh, cor(est.texto))
            fs = self.f.fonte("texto", 28)
            escrever(c, self.d.get("subtexto", "toque para seguir"), x + 172, cy + 38, fs, cor(est.suave))
            bw, bh = 220, 84
            bx, by = x + w - bw - 44, cy - bh / 2
            pc = fase(lt, tc, 0.25)
            bes = 1 - 0.12 * math.sin(pc * math.pi)
            with grupo(c, bx + bw / 2, cy, 1, bes):
                if clicou:
                    c.drawRRect(rrect(bx, by, bw, bh, bh / 2), tinta(cor("#2A2A2F")))
                    fb = self.f.fonte("forte", 32)
                    escrever(c, "Seguindo", bx + bw / 2 + 14, cy + 11, fb, cor(est.texto), "centro")
                    self.ic.desenhar(c, "lucide", "check", bx + 34, cy, 30, est.texto, 3)
                else:
                    desfocado(c, bx, by, bw, bh, bh / 2, 16, est.destaque, 0.5)
                    c.drawRRect(rrect(bx, by, bw, bh, bh / 2), tinta(cor(est.destaque)))
                    fb = self.f.fonte("forte", 34)
                    escrever(c, "Seguir", bx + bw / 2, cy + 12, fb, cor("#FFFFFF" if luminancia(est.destaque) < 0.6 else "#111111"), "centro")
            if clicou:  # corações subindo
                for k in range(5):
                    pk = fase(lt, tc + k * 0.06, 0.9)
                    if 0 < pk < 1:
                        hx = bx + bw / 2 + (k - 2) * 38 + math.sin(pk * 6 + k) * 14
                        hy = by - 20 - 190 * out_cubic(pk)
                        with grupo(c, hx, hy, 1 - pk, 0.6 + 0.6 * pk):
                            self.ic.desenhar(c, "lucide", "heart", hx, hy, 40, est.destaque, 2.4)
            # Cursor: entra pela direita/baixo e clica no botão.
            pm = in_out(fase(lt, 0.25, max(0.3, tc - 0.3)))
            mx = mix(x + w + 160, bx + bw * 0.62, pm)
            my = mix(cy + 260, cy + 10, pm)
            sc = 1 - 0.15 * math.sin(pc * math.pi)
            with grupo(c, mx, my, 1 - out_cubic(fase(lt, tc + 0.6, 0.3)), sc):
                c.save()
                c.translate(4, 6)
                self.ic.desenhar(c, "lucide", "mouse-pointer-2", mx + 22, my + 22, 64, "#000000", 2)
                c.restore()
                p = tinta(cor("#FFFFFF"))
                path = skia.Path()
                path.moveTo(mx, my)
                path.lineTo(mx + 46, my + 18)
                path.lineTo(mx + 24, my + 26)
                path.lineTo(mx + 16, my + 48)
                path.close()
                c.drawPath(path, p)
                c.drawPath(path, tinta(cor("#111111"), "stroke", 3))


class Busca(Elemento):
    """Barra de busca digitando + resultados aparecendo."""

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        claro = self.d.get("tema") == "claro"
        w, h = 900, 124
        cx, cy = self.centro("topo", h + 240)
        x, y = cx - w / 2, cy - h / 2 - 90
        a, esc, dy = entrada_padrao(pe, ps)
        q = self.d.get("texto", "")
        with grupo(c, cx, cy, a, esc, dy=dy):
            fundo = "#FFFFFF" if claro else "#18181B"
            tx = "#1F1F1F" if claro else est.texto
            desfocado(c, x, y + 14, w, h, h / 2, 26, "#000000", 0.45)
            c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor(fundo)))
            if not claro:
                c.drawRRect(rrect(x, y, w, h, h / 2), tinta(cor("#FFFFFF", 0.1), "stroke", 2))
            self.ic.desenhar(c, "lucide", "search", x + 70, y + h / 2, 44, "#7A7A82", 2.4)
            f = caber(self.f, "texto", q, w - 200, 42)
            n = int(clamp((lt - 0.35) * 16, 0, len(q)))
            wv = escrever(c, q[:n], x + 118, y + h / 2 + 15, f, cor(tx))
            if n < len(q) or (lt % 0.9) < 0.5:
                c.drawRect(skia.Rect.MakeXYWH(x + 122 + wv, y + h / 2 - 26, 3.5, 52), tinta(cor(est.destaque)))
            tr = 0.35 + len(q) / 16.0 + 0.35
            for k in range(3):
                pk = fase(lt, tr + k * 0.12, 0.4)
                if pk <= 0:
                    continue
                ry = y + h + 40 + k * 76
                with grupo(c, cx, ry + 30, out_cubic(pk), 1, dy=mix(24, 0, out_expo(pk))):
                    c.drawRRect(rrect(x + 20, ry, w - 40, 62, 18), tinta(cor("#FFFFFF" if claro else "#141416", 0.92)))
                    c.drawRRect(rrect(x + 50, ry + 18, (w - 260) * (0.9 - k * 0.18), 12, 6), tinta(cor(est.destaque if k == 0 else "#55555C", 0.9)))
                    c.drawRRect(rrect(x + 50, ry + 38, (w - 340) * (0.75 - k * 0.1), 9, 4.5), tinta(cor("#55555C", 0.6)))


class Marca(Elemento):
    """Logo grande de uma marca/app com brilho na cor dela."""

    entrada = 0.55

    def desenhar(self, c, lt, pe, ps, t):
        est = self.est
        cx, cy = self.centro("topo", 380)
        logo = re.sub(r"[^a-z0-9]", "", (self.d.get("logo") or "").lower())
        hexa = (self.ic.cor_media(self.d["imagem"]) if self.d.get("imagem") else self.ic.cor_marca(logo)) or est.destaque
        if luminancia(hexa) < 0.12:
            hexa = est.destaque
        a = out_cubic(pe) * (1 - out_cubic(ps))
        esc = mix(0.3, 1, out_elastic(pe) if pe < 1 else 1) * mix(1, 0.9, ps)
        flutua = math.sin(lt * 2.2) * 8
        brilho_radial(c, cx, cy, 340, hexa, 0.35 * a)
        with grupo(c, cx, cy + flutua, a, esc, giro=mix(-12, 0, out_back(pe))):
            cartao(c, cx - 140, cy - 140, 280, 280, 64, est, borda_cor=hexa, borda=3, brilho=0.8)
            self.ic.app(c, self.d, cx, cy, 150, est)
        if self.d.get("texto"):
            pt = fase(lt, 0.25, 0.4)
            f = caber(self.f, "titulo", self.d["texto"], self.W * 0.8, 64)
            with grupo(c, cx, cy + 230, a * out_cubic(pt), 1, dy=mix(30, 0, out_expo(pt))):
                escrever(c, self.d["texto"], cx, cy + 240, f, cor(est.texto), "centro", sombra=12)


from cena import CenaLivre  # noqa: E402

COMPONENTES = {
    "cena": CenaLivre,
    "pilula": Pilula, "janela": Janela, "contador": Contador, "rec": Rec, "apps": Apps,
    "comentario": Comentario, "passos": Passos, "cards": Cards, "notificacao": Notificacao,
    "grafico": Grafico, "comparacao": Comparacao, "seguir": Seguir, "busca": Busca, "marca": Marca,
}
