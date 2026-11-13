const card = p => `
    <a class="card" href="#product-${p.id}"> 
        <div class="ph" style="background-image: url('${p.img || 'assets/images/placeholder.jpg'}'); background-size: cover; background-position: center;"> 
            ${p.f ? '<span class="tag">Nổi bật</span>' : p.nw ? '<span class="tag">Mới</span>' : ''} 
        </div> 
        <div class="nm">${p.n}</div> 
        <div class="pr">${fmt(p.p)}</div> 
    </a>`;